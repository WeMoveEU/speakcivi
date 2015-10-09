<?php

require_once 'CRM/Core/Page.php';

class CRM_Bsd_Page_Confirm extends CRM_Core_Page {
  function run() {
    $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, true);
    $aid = CRM_Utils_Request::retrieve('aid', 'Positive', $this, true);
    $campaign_id = CRM_Utils_Request::retrieve('cid', 'Positive', $this, false);
    CRM_Core_Error::debug_var('CONFIRM $campaign_id', $campaign_id, false, true);
    $hash = CRM_Utils_Request::retrieve('hash', 'String', $this, true);
    $hash1 = sha1(CIVICRM_SITE_KEY . $id);
    if ($hash !== $hash1) {
      CRM_Core_Error::fatal("hash not matching");
    }

    /* Section: Group */
    $group_id = CRM_Core_BAO_Setting::getItem('BSD API Preferences', 'group_id');
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

    /* Section: Activity */
    if ($aid) {
      $scheduled_id = CRM_Core_OptionGroup::getValue('activity_status', 'Scheduled', 'name', 'String', 'value');
      $params = array(
        'sequential' => 1,
        'id' => $aid,
        'status_id' => $scheduled_id,
      );
      $result = civicrm_api3('Activity', 'get', $params);
      CRM_Core_Error::debug_var('CONFIRM $resultActivityGet', $result, false, true);
      if ($result['count'] == 1) {
        $completed_id = CRM_Core_OptionGroup::getValue('activity_status', 'Completed', 'name', 'String', 'value');
        $params['status_id'] = $completed_id;
        $result = civicrm_api3('Activity', 'create', $params);
        CRM_Core_Error::debug_var('CONFIRM $resultActivity-create', $result, false, true);
      }
    }

    /* Section: Country */
    $country = '';
    $bsd = new CRM_Bsd_Page_BSD();
    $bsd->setDefaults();
    $bsd->customFields = $bsd->getCustomFields($campaign_id);
    $language = $bsd->getLanguage();
    if ($language != '') {
      $tab = explode('_', $language);
      if (strlen($tab[0]) == 2) {
        $country = '/'.$tab[0];
      }
    }

    $url = "{$country}/post_confirm";
    CRM_Utils_System::redirect($url);
  }
}
