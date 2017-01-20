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

$path = 'sendconfirm.csv';
$sheet = file($path);

$log = 'sendconfirm.log';
$fp = fopen($log, 'a+');
foreach ($sheet as $id => $line) {

  // $email, $contactId, $activityId, $campaignId, $confirmationBlock, $share_utm_source = ''
  $row = str_getcsv($line, ",", '"');
  if ($row[4] == 'TRUE') {
    $confirmationBlock = true;
  } else {
    $confirmationBlock = false;
  }
  $params = array(
    'sequential' => 1,
    'toEmail' => $row[0],
    'contact_id' => $row[1],
    'activity_id' => $row[2],
    'campaign_id' => $row[3],
    'confirmation_block' => $confirmationBlock,
  );
  if (key_exists(5, $row) && $row[5]) {
    $params['share_utm_source'] = $row[5];
  }
  $result = civicrm_api3("Speakcivi", "sendconfirm", $params);
  $output = '"' . date('Y-m-d H:i:s') . "\";\"" . $row[0] . "\";" . $result['is_error'] . ";" . $result['version'] . ";" . $result['count'] . ";" . $result['values'] . "\n";
  fwrite($fp, $output);
  usleep(50000);
}
fclose($fp);
echo "Konec\n\n";
