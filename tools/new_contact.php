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


$bsd = new CRM_Bsd_Page_BSD();
$param = (object)array(
  'action_name' => 'Nazwa kampanii',
  'action_type' => 'petition',
  'action_technical_type' => 'act2.wemove.eu:petition',
  'external_id' => 108,
  'create_dt' => '2015-10-13T13:56:59.617+01:00',
  'cons_hash' => (object)array(
    'firstname' => 'Tomasz',
    'lastname' => 'Pietrzkowski',
//    'lastname' => '',
    'emails' => array(
      0 => (object)array(
        'email' => 'tomasz@chords.pl',
      )
    ),
    'addresses' => array(
      0 => (object)array(
        'zip' => '[pl] 02-222',
      ),
    ),
  ),
  'boolean_collection' => true,
  'comment' => 'Komentarz do petycji',
);
//var_dump($param);

$bsd->setDefaults();
$bsd->setCountry($param);
$bsd->campaign = $bsd->getCampaign($param->external_id);
$bsd->campaign = $bsd->setCampaign($param->external_id, $bsd->campaign);
if ($bsd->isValidCampaign($bsd->campaign)) {
  $bsd->campaignId = $bsd->campaign['id'];
} else {
  echo 'blad :-[';
  exit;
}
$result = $bsd->createContact($param);
print_r($result);

