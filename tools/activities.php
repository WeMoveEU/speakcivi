<?php

session_start();
$settingsFile = trim(implode('', file('path.inc'))).'/civicrm.settings.php';
define('CIVICRM_SETTINGS_PATH', $settingsFile);
define('CIVICRM_CLEANURL', 1);
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
$config = CRM_Core_Config::singleton();

$email_id = 480466;
$job_id = 30223;
$time = date('YmdHis');
echo $time."\n\n";

   $query = "UPDATE civicrm_mailing_event_delivered d
              JOIN civicrm_mailing_event_queue q ON d.event_queue_id = q.id 
              SET d.mailjet_time_stamp = %3
              WHERE q.job_id = %1 AND q.email_id = %2 AND d.mailjet_time_stamp = '1970-01-01'";
    $params = array(
      1 => array($job_id, 'Integer'),
      2 => array($email_id, 'Integer'),
      3 => array($time, 'String'),
    );
    CRM_Core_DAO::executeQuery($query, $params);


exit;

$post = new CRM_Speakcivi_Page_Confirm();

$post->contactId = 7034;
$post->campaignId = 16;
$aids = $post->findActivitiesIds($post->activityId, $post->campaignId, $post->contactId);
print_r($aids);

$post->setActivitiesStatuses($post->activityId, $aids, 'Completed');
