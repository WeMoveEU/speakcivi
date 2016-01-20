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
  'external_id' => 23,
  'create_dt' => '2015-10-13T13:56:59.617+01:00',
  'cons_hash' => (object)array(
    'firstname' => 'Tomasz',
    'lastname' => 'Pietrzkowski [M]',
    'emails' => array(
      0 => (object)array(
        'email' => 'tomasz.pietrzkowski8@chords.pl',
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
// share:
//$param = (object)array(
//  'action_name' => 'Nazwa kampanii',
//  'action_type' => 'share',
//  'action_technical_type' => 'act2.wemove.eu:share',
//  'external_id' => 23,
//  'create_dt' => '2015-10-13T13:56:59.617+01:00',
//  'cons_hash' => (object)array(
//    'firstname' => 'Tomasz',
////    'lastname' => 'Pietrzkowski',
//    'lastname' => '',
//    'emails' => array(
//      0 => (object)array(
//        'email' => 'tomek@chords.pl',
//      )
//    ),
//    'addresses' => array(
//      0 => (object)array(
//        //'zip' => '[pl] 01-111',
//        'zip' => '[de] 48329 Havixbeck',
//      ),
//    ),
//  ),
//  'boolean_collection' => true,
//  'comment' => 'Komentarz do petycji',
//);
//var_dump($param);

$speakcivi->setDefaults();
$speakcivi->setCountry($param);
echo '$speakcivi->country: '. $speakcivi->country."\n";
echo '$speakcivi->postal_code: '.$speakcivi->postalCode."\n\n\n";
$speakcivi->campaignObj = new CRM_Speakcivi_Logic_Campaign();
$speakcivi->campaign = $speakcivi->campaignObj->getCampaign($param->external_id);
$speakcivi->campaign = $speakcivi->campaignObj->setCampaign($param->external_id, $speakcivi->campaign);
if ($speakcivi->campaignObj->isValidCampaign($speakcivi->campaign)) {
  $speakcivi->campaignId = $speakcivi->campaign['id'];
  $speakcivi->campaignObj->customFields = $speakcivi->campaignObj->getCustomFields($speakcivi->campaignId);
  $speakcivi->locale = $speakcivi->campaignObj->getLanguage();
  echo "locale: \n";
  var_dump($speakcivi->locale);
  echo "\n\n";
} else {
  echo 'blad :-[';
  exit;
}
$gender = $speakcivi->getGenderId($param->cons_hash->lastname);
echo $param->cons_hash->lastname."\n";
echo '$gender: '. $gender."\n";
$lastname = $speakcivi->cleanLastname($param->cons_hash->lastname);
echo 'lastname: '.$lastname."\n\n";
$result = $speakcivi->createContact($param);
print_r($result);

$genderId = $speakcivi->getGenderId($param->cons_hash->lastname);
$genderShortcut = $speakcivi->getGenderShortcut($param->cons_hash->lastname);

echo "genderId: ".$genderId."\n";
echo "genderShortcut: ".$genderShortcut."\n";
var_dump($genderId);
var_dump($genderShortcut);
var_dump($speakcivi->genderFemaleValue);
var_dump($speakcivi->genderMaleValue);
var_dump($speakcivi->genderUnspecifiedValue);
