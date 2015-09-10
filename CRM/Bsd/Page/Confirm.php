<?php

require_once 'CRM/Core/Page.php';

class CRM_Bsd_Page_Confirm extends CRM_Core_Page {
  function run() {
    $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, true);
    $hash = CRM_Utils_Request::retrieve('hash', 'String', $this, true);
    $hash1 = sha1(CIVICRM_SITE_KEY . $id);
    $group_id = 42;
    if ($hash !== $hash1) {
      CRM_Core_Error::fatal("hash not matching");
    }
    $result = civicrm_api3('GroupContact', 'get', array(
      'sequential' => 1,
      'contact_id' => $id,
      'group_id' => $group_id,
      'status' => "Pending"
    ));
    CRM_Core_Error::debug_var('CONFIRM $resultGroupContact-get', $result, false, true);

    if ($result['count'] == 1) {
      $params = array(
        'id' => $result["id"],
        'status' => "Added",
      );
    } else {
      $params = array(
        'sequential' => 1,
        'contact_id' => $id,
        'group_id' => $group_id,
        'status' => "Added",
      );
    }
    $result = civicrm_api3('GroupContact', 'create', $params);
    CRM_Core_Error::debug_var('CONFIRM $resultGroupContact-create', $result, false, true);

    $url = "/post_confirm";
    return CRM_Utils_System::redirect($url);
  }
}
