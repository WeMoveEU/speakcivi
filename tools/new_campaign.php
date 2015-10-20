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


// tests:

$speakcivi = new CRM_Speakcivi_Page_Speakcivi();

$param = (object)array(
  'external_id' => 9,
);

$campaign = $speakcivi->getCampaign($param->external_id);
echo '$campaign GET: ';
print_r($campaign);

$campaign = $speakcivi->setCampaign($param, $campaign);
echo '$campaign NEW: ';
print_r($campaign);

