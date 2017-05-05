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

$query = "SELECT
            contact_id, min(campaign_id) campaign_id
          FROM tmp_unsub
          WHERE done = 0
          GROUP BY contact_id
          LIMIT 500";
$dao = CRM_Core_DAO::executeQuery($query);
while ($dao->fetch()) {
  echo $dao->contact_id . ' ' . $dao->campaign_id . "\n";
  CRM_Speakcivi_Logic_Activity::leave(
    $dao->contact_id,
    'unsubscribe',
    $dao->campaign_id,
    0,
    date('YmdHis'),
    'Added by SpeakCivi Script'
  );
  $queryUpdate = "UPDATE tmp_unsub SET done = 1 WHERE contact_id = %1";
  $params = array(
    1 => array($dao->contact_id, 'Integer'),
  );
  CRM_Core_DAO::executeQuery($queryUpdate, $params);
}
