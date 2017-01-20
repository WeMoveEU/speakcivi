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


$post = new CRM_Speakcivi_Page_Confirm();

$post->contactId = 7034;
$post->campaignId = 16;
$aids = $post->findActivitiesIds($post->activityId, $post->campaignId, $post->contactId);
print_r($aids);

$post->setActivitiesStatuses($post->activityId, $aids, 'Completed');
