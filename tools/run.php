<?php

session_start();
$settingsFile = trim(implode('', file('path.inc'))).'/civicrm.settings.php';
define('CIVICRM_SETTINGS_PATH', $settingsFile);
define('CIVICRM_CLEANURL', 1);
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

// old speakout petition
/*$param = (object)array(
  'action_name' => 'Testowa kampania',
  'action_type' => 'petition',
  'action_technical_type' => 'act2.wemove.eu:petition',
  'external_id' => 49,
  'create_dt' => '2016-01-08T11:56:59.617+01:00',
  'cons_hash' => (object)array(
    'firstname' => 'Tomasz',
    'lastname' => 'Pietrzkowski [M]',
    'emails' => array(
      0 => (object)array(
        'email' => 'scardinius@chords.pl',
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
);*/

// old speakout share
/*$param = (object)array(
  'action_name' => 'tomasz_test-PL',
  'action_type' => 'share',
  'action_technical_type' => 'act2.wemove.eu:share',
  'external_id' => 23,
  'create_dt' => '2016-08-18T15:22:06.075+01:00',
  'cons_hash' => (object)array(
    'firstname' => 'Tomasz',
    'lastname' => 'Pietrzkowski [M]',
    'emails' => array(
      0 => (object)array(
        'email' => 'scardinius@chords.pl',
      )
    ),
  ),
  'source' => array(
    'source' => 'utm_sourceA',
    'medium' => 'utm_mediumB',
    'campaign' => 'utm_campaignC',
  ),
  'metadata' => (object)array(
    'tracking_codes' => (object)array(
      'source' => 'member_b34404d7',
      'medium' => 'facebook',
      'campaign' => '123456qwerty',
      'content' => 'b34404d7',
    )
  ),
);*/

// new speakout (you.wemove.eu) petition
/*$param = (object)array(
  'action_type' => 'petition',
  'action_technical_type' => 'you.wemove.eu:petition',
  'create_dt' => '2016-03-22T12:40:12.531Z',
  'action_name' => 'diem25-GR',
  'external_id' => 10007,
  'cons_hash' => (object)array(
    'firstname' => 'Tomasz',
    'lastname' => 'Pietrzkowski',
    'emails' => array(
      0 => (object)array(
        'email' => 'scardinius@chords.pl',
      )
    ),
    'addresses' => array(
      0 => (object)array(
        'zip' => '[pl] 02-222',
      ),
    ),
  ),
  'metadata' => (object)array(
    "sign_boolean" => null,
    "sign_answer" => null,
    "sign_comment" => null,
  ),
  'source' => (object)array(
    "source" => "generic",
    "medium" => "facebook",
    "campaign" => "diem25-GR",
  ),
);*/

// new speakout (you.wemove.eu) share
/*$param = (object)array(
  'action_type' => 'share',
  'action_technical_type' => 'you.wemove.eu:share',
  'create_dt' => '2016-09-22T10:05:19.752Z',
  'action_name' => 'tomasz-test-you-PL',
  'external_id' => 10015,
  'cons_hash' => (object)array(
    'firstname' => 'Tomasz',
    'lastname' => 'Pietrzkowski',
    'emails' => array(
      0 => (object)array(
        'email' => 'scardinius@chords.pl',
      )
    ),
    'addresses' => array(
      0 => (object)array(
        // 'zip' => '[pl] 02-222', // valid
        'zip' => null,
        'country' => 'uk',
      ),
    ),
  ),
  'source' => (object)array(
    "source" => "generic",
    "medium" => "web",
    "campaign" => "tomasz-test-you-PL",
  ),
  'metadata' => (object)array(
    'tracking_codes' => (object)array(
      'source' => 'member_b34404d7',
      'medium' => 'facebook',
      'campaign' => '123456qwerty',
      'content' => 'b34404d7',
    )
  ),
);*/


$speakcivi = new CRM_Speakcivi_Page_Speakcivi();
$speakcivi->runParam($param);
print_r($speakcivi);
print_r($param);
exit;
