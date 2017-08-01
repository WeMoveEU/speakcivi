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
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

function debug($msg) {
  echo time(), ': ', $msg, "\n";
}

set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line, array $err_context) {
  if (0 === error_reporting()) { return false; }
  switch ($err_severity) {
    case E_USER_ERROR:
      debug("Uncaught E_USER_ERROR: forcing exception");
      throw new Exception($err_msg);
  }
  return false;
});

const SC_LOAD_INDEX = 1; // index of the avg [1m,5m,15m]
const SC_MAX_LOAD = 3;
const SC_LOAD_CHECK_FREQ = 100;
const SC_COOLING_PERIOD = 20; // seconds
const SC_RETRY_DELAY = 60000; // milliseconds

$msg_since_check = 0;
$arguments = getopt('q:e:r:');
$queue_name = $arguments['q'];
$error_queue = $arguments['e'];
$retry_exchange = $arguments['r'];

function connect() {
  return new AMQPStreamConnection(
    CIVICRM_AMQP_HOST, CIVICRM_AMQP_PORT,
    CIVICRM_AMQP_USER, CIVICRM_AMQP_PASSWORD, CIVICRM_AMQP_VHOST);
}

/**
 * If $retry is trueish, nack the message without re-queue and send it to the retry exchange.
 * Otherwise if an error queue is defined, send it to that queue through the direct exchange.
 * Otherwise nack and re-deliver the message to the originating queue.
 */
function handleError($msg, $error, $retry=false) {
  global $error_queue, $retry_exchange;
  CRM_Core_Error::debug_var("SPEAKCIVI AMQP", $error, true, true);
  $channel = $msg->delivery_info['channel'];

  if ($retry && $retry_exchange != NULL) {
    $channel->basic_nack($msg->delivery_info['delivery_tag']);
    $new_msg = new AMQPMessage($msg->body);
    $headers = new AMQPTable(array('x-delay' => SC_RETRY_DELAY));
    $new_msg->set('application_headers', $headers);
    $channel->basic_publish($new_msg, $retry_exchange, $msg->delivery_info['routing_key']);
  } else if ($error_queue != NULL) {
    $channel->basic_nack($msg->delivery_info['delivery_tag']);
    $channel->basic_publish($msg, '', $error_queue);
  } else {
    $channel->basic_nack($msg->delivery_info['delivery_tag'], false, true);
  }
  
  //In some cases (e.g. a lost connection), dying and respawning can solve the problem
  die(1);
}

/**
 * Check whether error is linked with lost connection to smtp server.
 *
 * @param $sessionStatus
 *
 * @return bool
 */
function isConnectionLostError($sessionStatus) {
  if (is_array($sessionStatus) && array_key_exists('title', $sessionStatus[0]) && $sessionStatus['title'] == 'Mailing Error') {
    return !!strpos($sessionStatus['text'], 'Connection lost to authentication server');
  }
  return false;
}

$callback = function($msg) {
  global $msg_since_check;
  try {
    $json_msg = json_decode($msg->body);
    if ($json_msg) {
      $msg_handler = new CRM_Speakcivi_Page_Speakcivi();
      try {
        $result = $msg_handler->runParam($json_msg);
        if ($result == 1) {
          $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        } elseif ($result == -1) {
          handleError($msg, "runParams unsupported action type: " . $json_msg->action_type);
        } else {
          $session = CRM_Core_Session::singleton();
          $retry = isConnectionLostError($session->getStatus());
          handleError($msg, "runParams returned error code", $retry);
        }
      } catch (CiviCRM_API3_Exception $ex) {
        $extraInfo = $ex->getExtraParams();
        $retry = strpos(CRM_Utils_Array::value('debug_information', $extraInfo), "try restarting transaction");
        handleError($msg, CRM_Core_Error::formatTextException($ex), $retry);
      } catch (CRM_Speakcivi_Exception $ex) {
        if ($ex->getErrorCode() == 1) {
          CRM_Core_Error::debug_log_message('SPEAKCIVI AMQP ' . $ex->getMessage());
          CRM_Core_Error::debug_var("SPEAKCIVI AMQP", $json_msg, true, true);
          $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        } else {
          handleError($msg, CRM_Core_Error::formatTextException($ex));
        }
      } catch (Exception $ex) {
        handleError($msg, CRM_Core_Error::formatTextException($ex));
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
$channel->basic_qos(null, SC_LOAD_CHECK_FREQ, null);
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
