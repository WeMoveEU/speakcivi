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


// tests:

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'opt_in');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'url_speakout');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'group_id');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'default_language_group_id');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'language_group_name_suffix');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'language_tag_name_prefix');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'default_language');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_template_id');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_language');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_sender_mail');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_url_campaign');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_utm_campaign');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_twitter_share_text');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_subject_new');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_subject_current');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_message_new');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_message_current');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_activity_source');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_activity_medium');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_activity_campaign');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_contact_source');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_contact_medium');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_contact_campaign');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_tracking_codes_source');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_tracking_codes_medium');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_tracking_codes_campaign');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_tracking_codes_content');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'from');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'country_lang_mapping');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'activity_type_join');
var_dump($result);

$result = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'activity_type_leave');
var_dump($result);
