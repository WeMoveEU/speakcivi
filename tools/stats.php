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

$filename = 'Settings.buildQuickForm.csv';
$rows = CRM_Speakcivi_Tools_Stat::buildRows($filename);
$calcs = CRM_Speakcivi_Tools_Stat::calculate($filename, $rows);
$calcs = CRM_Speakcivi_Tools_Stat::replaceDots($calcs);
CRM_Speakcivi_Tools_Stat::saveReport($filename, $calcs);
