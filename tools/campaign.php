<?php

session_start();
//require_once '../../civicrm.config.php';

$settingsFile = '../civicrm.settings.php';
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

print_r($config);
