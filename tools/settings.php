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

$result = CRM_Core_BAO_Setting::getItem('BSD API Preferences', 'opt_in');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('BSD API Preferences', 'group_id');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('BSD API Preferences', 'default_template_id');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('BSD API Preferences', 'default_language');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('BSD API Preferences', 'field_template_id');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('BSD API Preferences', 'field_language');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('BSD API Preferences', 'field_sender_mail');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('BSD API Preferences', 'from');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('BSD API Preferences', 'country_lang_mapping');
var_dump($result);
