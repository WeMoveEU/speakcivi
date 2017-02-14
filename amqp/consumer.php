<?php

session_start();
$settingsFile = trim(implode('', file('../tools/path.inc'))).'/civicrm.settings.php';
define('CIVICRM_SETTINGS_PATH', $settingsFile);
define('CIVICRM_CLEANURL', 1);
define('CIVICRM_MAILER_TRANSIENT', 1);

$error = @include_once( $settingsFile );
if ( $error == false ) {
  echo "Could not load the settings file at: {$settingsFile}\n";
  exit( );
}

// Load class loader
global $civicrm_root;
require_once $civicrm_root . '/CRM/Core/ClassLoader.php';
CRM_Core_ClassLoader::singleton()->register();
require_once 'CRM/Core/Config.php';
$civicrm_config = CRM_Core_Config::singleton();
//Load CMS with user id 1
CRM_Utils_System::loadBootStrap(array('uid' => 1), TRUE, FALSE);

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;

function debug($msg) {
  echo time(), ': ', $msg, "\n";
}

const SC_LOAD_INDEX = 1; // index of the avg [1m,5m,15m]
const SC_MAX_LOAD = 3;
const SC_LOAD_CHECK_FREQ = 100;
const SC_COOLING_PERIOD = 20;

$msg_since_check = 0;
$arguments = getopt('q:e:');
$queue_name = $arguments['q'];
$error_queue = $arguments['e'];

function connect() {
  return new AMQPStreamConnection(
    CIVICRM_AMQP_HOST, CIVICRM_AMQP_PORT,
    CIVICRM_AMQP_USER, CIVICRM_AMQP_PASSWORD, CIVICRM_AMQP_VHOST);
}

function handleError($msg, $error) {
  global $error_queue;
  CRM_Core_Error::debug_var("SPEAKCIVI AMQP", $error, true, true);
  $channel = $msg->delivery_info['channel'];
  if ($error_queue != NULL) {
    $channel->basic_publish($msg, '', $error_queue);
    $channel->basic_ack($msg->delivery_info['delivery_tag']);
  } else {
    $channel->basic_nack($msg->delivery_info['delivery_tag'], false, true);
  }
  //In some cases (e.g. a lost connection), dying and respawning can solve the problem
  die(1);
}

$callback = function($msg) {
  global $msg_since_check;
  try {
    $json_msg = json_decode($msg->body);
    if ($json_msg) {
      $msg_handler = new CRM_Speakcivi_Page_Speakcivi();
      try {
        if ($msg_handler->runParam($json_msg)) {
          $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        } else {
          handleError($msg, "runParams returned error code");
        }
      } catch (CiviCRM_API3_Exception $ex) {
        CRM_Core_Error::debug_var('CiviCRM_API3_Exception $ex', $ex->getExtraParams());
        handleError($msg, "CiviCRM_API3_Exception occured");
      } catch (Exception $ex) {
        CRM_Core_Error::debug_var('Exception $ex', $ex->getMessage());
        handleError($msg, "Exception occured");
      }
    } else {
      handleError($msg, "Could not decode " . $msg->body);
    }
  } catch (Exception $ex) {
    handleError($msg, CRM_Core_Error::formatTextException($ex));
  } finally {
    $msg_since_check++;
  }
};

$connection = connect();
$channel = $connection->channel();
debug('Waiting for messages. To exit press CTRL+C...');
while (true) {
  while (count($channel->callbacks)) {
    if ($msg_since_check >= SC_LOAD_CHECK_FREQ) {
      $load = sys_getloadavg()[SC_LOAD_INDEX];
      if ($load > SC_MAX_LOAD) {
        debug('Cancelling subscription...');
        $channel->basic_cancel($cb_name);
        $channel->basic_recover(true);
        continue;
      } else {
        $msg_since_check = 0;
      }
    }
    $channel->wait();
  }

  $load = sys_getloadavg()[SC_LOAD_INDEX];
  if ($load > SC_MAX_LOAD) {
    //CRM_Core_Error::debug_var("SPEAKCIVI AMQP", "Current load greater than ".SC_MAX_LOAD.", suspending polling...\n", true, true);
    debug('Suspending polling...');
    $channel->close();
    $connection->close();
    sleep(SC_COOLING_PERIOD);
  } else {
    if (!$connection->isConnected()) {
      debug('Reconnecting...');
      $connection = connect();
      $channel = $connection->channel();
      $channel->basic_qos(null, SC_LOAD_CHECK_FREQ, null);
    }
    debug('Starting subscription...');
    $cb_name = $channel->basic_consume($queue_name, '', false, false, false, false, $callback);
  }
}
