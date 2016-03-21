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

$urlSpeakout = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'url_speakout');
echo '$urlSpeakout: '.$urlSpeakout."\n\n";

if ($urlSpeakout) {
  $campaignObj = new CRM_Speakcivi_Logic_Campaign();

  $param = (object)array(
    'external_id' => 49,
    'action_technical_type' => 'you.wemove.eu:petition',
  );

  $campaign = $campaignObj->getCampaign($param->external_id);
  echo '$campaign GET: ';
  print_r($campaign);

  $campaign = $campaignObj->setCampaign($param->external_id, $campaign, $param);
  echo '$campaign NEW: ';
  print_r($campaign);

  $campaignObj->customFields = $campaignObj->getCustomFields($campaign['id']);
  echo 'customFields: ';
  print_r($campaignObj->customFields);
}
