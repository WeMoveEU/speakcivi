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

CRM_Core_OptionGroup::getAssoc('email_greeting', $group, false, 'name');
print_r($group);


$dict = new CRM_WeAct_Dictionary();
$dict->parseGroupEmailGreeting();
print_r($dict->emailGreetingIds);

$locales = array(
  'fr_FR:',
  'fr_FR:F',
  'fr_FR:M',
  'de_DE:',
  'de_DE:M',
  'de_DE:FG',
);

foreach ($locales as $loc) {
  echo $loc.' -> '.print_r($dict->parseLocaleGenderShortcut($loc), true)."\n";
}


echo $dict->getEmailGreetingId('fr_FR', '')."\n";
echo $dict->getEmailGreetingId('fr_FR', 'F')."\n";
echo $dict->getEmailGreetingId('fr_FR', 'M')."\n";
echo $dict->getEmailGreetingId('de_DE', '')."\n";
echo $dict->getEmailGreetingId('de_DE', 'F')."\n";
echo $dict->getEmailGreetingId('de_DE', 'M')."\n";
echo $dict->getEmailGreetingId('es_ES', '')."\n";
echo $dict->getEmailGreetingId('es_ES', 'F')."\n";
echo $dict->getEmailGreetingId('es_ES', 'M')."\n";
