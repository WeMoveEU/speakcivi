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
$post->contactId = 100;
$post->campaignId = 8;
$campaign = new CRM_Speakcivi_Logic_Campaign($post->campaignId);
$locale = $campaign->getLanguage();
$language = substr($locale, 0, 2);
$post->setLanguageGroup($post->contactId, $language);

$post->setLanguageTag($post->contactId, $language);

echo "\n\n";
