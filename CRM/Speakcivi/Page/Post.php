<?php

require_once 'CRM/Core/Page.php';

class CRM_Speakcivi_Page_Post extends CRM_Core_Page {

  public $contact_id = 0;

  public $activity_id = 0;

  public $campaign_id = 0;

  /**
   * Set values from request.
   *
   * @throws Exception
   */
  public function setValues() {
    $this->contact_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, true);
    $this->activity_id = CRM_Utils_Request::retrieve('aid', 'Positive', $this, false);
    $this->campaign_id = CRM_Utils_Request::retrieve('cid', 'Positive', $this, false);
    $hash = CRM_Utils_Request::retrieve('hash', 'String', $this, true);
    $hash1 = sha1(CIVICRM_SITE_KEY . $this->contact_id);
    if ($hash !== $hash1) {
      CRM_Core_Error::fatal("hash not matching");
    }
  }


  /**
   * Get country prefix based on campaign id.
   *
   * @param int $campaign_id
   *
   * @return string
   */
  public function getCountry($campaign_id) {
    $country = '';
    if ($campaign_id > 0) {
      $speakcivi = new CRM_Speakcivi_Page_Speakcivi();
      $speakcivi->setDefaults();
      $speakcivi->customFields = $speakcivi->getCustomFields($campaign_id);
      $language = $speakcivi->getLanguage();
      if ($language != '') {
        $tab = explode('_', $language);
        if (strlen($tab[0]) == 2) {
          $country = '/'.$tab[0];
        }
      }
    }
    return $country;
  }


  /**
   * Set new activity status for Scheduled activity.
   *
   * @param int $activity_id
   * @param string $status
   *
   * @throws CiviCRM_API3_Exception
   */
  public function setActivityStatus($activity_id, $status = 'optout') {
    if ($activity_id > 0) {
      $scheduled_id = CRM_Core_OptionGroup::getValue('activity_status', 'Scheduled', 'name', 'String', 'value');
      $params = array(
        'sequential' => 1,
        'id' => $activity_id,
        'status_id' => $scheduled_id,
      );
      $result = civicrm_api3('Activity', 'get', $params);
      if ($result['count'] == 1) {
        $new_status_id = CRM_Core_OptionGroup::getValue('activity_status', $status, 'name', 'String', 'value');
        $params['status_id'] = $new_status_id;
        $result = civicrm_api3('Activity', 'create', $params);
      }
    }
  }


  /**
   * Set Added status for group. If group is not assigned to contact, It is added.
   *
   * @param int $contact_id
   * @param int $group_id
   *
   * @throws CiviCRM_API3_Exception
   */
  public function setGroupStatus($contact_id, $group_id) {
    $result = civicrm_api3('GroupContact', 'get', array(
      'sequential' => 1,
      'contact_id' => $contact_id,
      'group_id' => $group_id,
      'status' => "Pending"
    ));

    if ($result['count'] == 1) {
      $params = array(
        'id' => $result["id"],
        'status' => "Added",
      );
    } else {
      $params = array(
        'sequential' => 1,
        'contact_id' => $contact_id,
        'group_id' => $group_id,
        'status' => "Added",
      );
    }
    $result = civicrm_api3('GroupContact', 'create', $params);
  }


  function findActivityIds($activity_id, $campaign_id, $contact_id) {
    $aids = array();
    if (!$activity_id && $campaign_id) {
      $activityTypeId = CRM_Core_OptionGroup::getValue('activity_type', 'Petition', 'name', 'String', 'value');
      $activityStatusId = CRM_Core_OptionGroup::getValue('activity_status', 'Scheduled', 'name', 'String', 'value');
      $query = "SELECT a.id
                FROM civicrm_activity a JOIN civicrm_activity_contact ac ON a.id = ac.activity_id
                WHERE ac.contact_id = %1 AND activity_type_id = %2 AND a.status_id = %3 AND a.campaign_id = %4";
      $params = array(
        1 => array($contact_id, 'Integer'),
        2 => array($activityTypeId, 'Integer'),
        3 => array($activityStatusId, 'Integer'),
        4 => array($campaign_id, 'Integer'),
      );
      $results = CRM_Core_DAO::executeQuery($query, $params);
      while ($results->fetch()) {
        $aids[] = $results->id;
      }
    }
    return $aids;
  }
}
