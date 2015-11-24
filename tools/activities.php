<?php

session_start();
$settingsFile = trim(implode('', file('path.inc'))).'/civicrm.settings.php';
define('CIVICRM_SETTINGS_PATH', $settingsFile);
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

$post->contact_id = 7034;
$post->campaign_id = 16;
$aids = $post->findActivitiesIds($post->activity_id, $post->campaign_id, $post->contact_id);
print_r($aids);

$post->setActivitiesStatuses($post->activity_id, $aids, 'Completed');
