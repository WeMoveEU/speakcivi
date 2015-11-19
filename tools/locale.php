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


require_once 'api/v3/Speakcivi.php';

$locales = array(
  'de_DE',
  'el_GR',
  'en_GB',
  'es_ES',
  'fr_FR',
  'it_IT',
  'pl_PL',
  'sv_SE',
);

foreach ($locales as $locale) {
  $lc = getLocale($locale);
  echo "\n".$locale.": \n";
  print_r($lc);
}
