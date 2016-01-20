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


// tests on localhost:
// group id = 9
// campaign id = 8


$speakcivi = new CRM_Speakcivi_Page_Speakcivi();
// petition:
$param = (object)array(
  'action_name' => 'Nazwa kampanii',
  'action_type' => 'petition',
  'action_technical_type' => 'act2.wemove.eu:petition',
  'external_id' => 49,
  'create_dt' => '2016-01-05T14:50:00.617+01:00',
  'cons_hash' => (object)array(
    'firstname' => 'Tomasz',
    'lastname' => 'Pietrzkowski',
    'emails' => array(
      0 => (object)array(
        'email' => 'tomasz@caltha.eu',
      )
    ),
    'addresses' => array(
      0 => (object)array(
        'zip' => '[pl] 02-222',
      ),
    ),
  ),
  'comment' => 'Komentarz do petycji',
);

var_dump($param);

$speakcivi->setDefaults();
$speakcivi->setCountry($param);
$speakcivi->campaignObj = new CRM_Speakcivi_Logic_Campaign();
$speakcivi->campaign = $speakcivi->campaignObj->getCampaign($param->external_id);
$speakcivi->campaign = $speakcivi->campaignObj->setCampaign($param->external_id, $speakcivi->campaign);
if ($speakcivi->campaignObj->isValidCampaign($speakcivi->campaign)) {
  $speakcivi->campaignId = $speakcivi->campaign['id'];
  $speakcivi->campaignObj->customFields = $speakcivi->campaignObj->getCustomFields($speakcivi->campaignId);
  $speakcivi->locale = $speakcivi->campaignObj->getLanguage();
} else {
  echo 'blad :-[';
  exit;
}
$speakcivi->petition($param);
