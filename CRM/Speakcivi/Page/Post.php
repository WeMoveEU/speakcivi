<?php

require_once 'CRM/Core/Page.php';

class CRM_Speakcivi_Page_Post extends CRM_Core_Page {

  public $contactId = 0;

  public $activityId = 0;

  public $campaignId = 0;


  /**
   * Set values from request.
   *
   * @throws Exception
   */
  public function setValues() {
    $this->contactId = CRM_Utils_Request::retrieve('id', 'Positive', $this, true);
    $this->activityId = CRM_Utils_Request::retrieve('aid', 'Positive', $this, false);
    $this->campaignId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, false);
    $hash = CRM_Utils_Request::retrieve('hash', 'String', $this, true);
    $hash1 = sha1(CIVICRM_SITE_KEY . $this->contactId);
    if ($hash !== $hash1) {
      CRM_Core_Error::fatal("hash not matching");
    }
  }


  /**
   * Get country prefix based on campaign id.
   *
   * @param int $campaignId
   *
   * @return string
   */
  public function getCountry($campaignId) {
    $country = '';
    if ($campaignId > 0) {
      $campaign = new CRM_Speakcivi_Logic_Campaign($campaignId);
      $language = $campaign->getLanguage();
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
   * @param int $activityId
   * @param string $status
   * @param string $location
   *
   * @throws CiviCRM_API3_Exception
   */
  public function setActivityStatus($activityId, $status = 'optout', $location = '') {
    if ($activityId > 0) {
      $scheduledId = CRM_Speakcivi_Logic_Activity::getStatusId('Scheduled');
      $params = array(
        'sequential' => 1,
        'id' => $activityId,
        'status_id' => $scheduledId,
      );
      $result = civicrm_api3('Activity', 'get', $params);
      if ($result['count'] == 1) {
        $newStatusId = CRM_Speakcivi_Logic_Activity::getStatusId($status);
        $params['status_id'] = $newStatusId;
        $params['location'] = $location;
        civicrm_api3('Activity', 'create', $params);
      }
    }
  }


  /**
   * Set acitivity status for each activities.
   *
   * @param integer $activityId
   * @param array $aids array of activities ids
   * @param string $status
   * @param string $location
   */
  public function setActivitiesStatuses($activityId, $aids, $status = 'Completed', $location = '') {
    if (is_array($aids) && count($aids) > 0) {
      foreach ($aids as $aid) {
        $this->setActivityStatus($aid, $status, $location);
      }
    } else {
      $this->setActivityStatus($activityId, $status, $location);
    }
  }


  /**
   * Check If contact is member of group on given status
   *
   * @param $contactId
   * @param $groupId
   * @param $status
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  private function isGroupContact($contactId, $groupId, $status = "Added") {
    $result = civicrm_api3('GroupContact', 'get', array(
      'sequential' => 1,
      'contact_id' => $contactId,
      'group_id' => $groupId,
      'status' => $status
    ));
    return (int)$result['id'];
  }


  /**
   * Check If contact is member of group on Added status
   *
   * @param $contactId
   * @param $groupId
   *
   * @return int
   */
  public function isGroupContactAdded($contactId, $groupId) {
    return $this->isGroupContact($contactId, $groupId, "Added");
  }


  /**
   * Check If contact is member of group on Removed status
   *
   * @param $contactId
   * @param $groupId
   *
   * @return int
   */
  public function isGroupContactRemoved($contactId, $groupId) {
    return $this->isGroupContact($contactId, $groupId, "Removed");
  }


  /**
   * Set given status for group
   *
   * @param $contactId
   * @param $groupId
   * @param $status
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function setGroupContact($contactId, $groupId, $status = "Added") {
    $params = array(
      'sequential' => 1,
      'contact_id' => $contactId,
      'group_id' => $groupId,
      'status' => $status,
    );
    civicrm_api3('GroupContact', 'create', $params);
  }


  /**
   * Set Added status for group
   *
   * @param $contactId
   * @param $groupId
   */
  public function setGroupContactAdded($contactId, $groupId) {
    $this->setGroupContact($contactId, $groupId, "Added");
  }


  /**
   * Set Removed status for group
   *
   * @param $contactId
   * @param $groupId
   */
  public function setGroupContactRemoved($contactId, $groupId) {
    $this->setGroupContact($contactId, $groupId, "Removed");
  }


  /**
   * Set Added status for group. If group is not assigned to contact, It is added.
   *
   * @param int $contactId
   * @param int $groupId
   *
   * @throws CiviCRM_API3_Exception
   */
  public function setGroupStatus($contactId, $groupId) {
    $result = civicrm_api3('GroupContact', 'get', array(
      'sequential' => 1,
      'contact_id' => $contactId,
      'group_id' => $groupId,
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
        'contact_id' => $contactId,
        'group_id' => $groupId,
        'status' => "Added",
      );
    }
    $result = civicrm_api3('GroupContact', 'create', $params);
  }


  /**
   * Set language group for contact based on language of campaign
   *
   * @param int $contactId
   * @param string $language Language in format en, fr, de, pl etc.
   */
  public function setLanguageGroup($contactId, $language) {
    if ($language) {
      $languageGroupNameSuffix = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'language_group_name_suffix');
      $defaultLanguageGroupId = (int)CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'default_language_group_id');
      if (!$this->checkLanguageGroup($contactId, $defaultLanguageGroupId, $languageGroupNameSuffix)) {
        $languageGroupId = $this->findLanguageGroupId($language, $languageGroupNameSuffix);
        if ($languageGroupId) {
          $this->setGroupStatus($contactId, $languageGroupId);
          $this->deleteLanguageGroup($contactId, $defaultLanguageGroupId);
        } else {
          $this->setGroupStatus($contactId, $defaultLanguageGroupId);
        }
      }
    }
  }


  /**
   * Get language group id based on language shortcut
   *
   * @param string $language Example: en, es, fr...
   * @param string $languageGroupNameSuffix
   *
   * @return int
   */
  public function findLanguageGroupId($language, $languageGroupNameSuffix) {
    $result = civicrm_api3('Group', 'get', array(
      'sequential' => 1,
      'name' => $language.$languageGroupNameSuffix,
      'return' => 'id',
    ));
    if ($result['count'] == 1) {
      return $result['id'];
    }
    return 0;
  }


  /**
   * Check if contact has already at least one language group. Default group is skipping.
   *
   * @param int $contactId
   * @param int $defaultLanguageGroupId
   * @param string $languageGroupNameSuffix
   *
   * @return bool
   */
  public function checkLanguageGroup($contactId, $defaultLanguageGroupId, $languageGroupNameSuffix) {
    $query = "SELECT count(gc.id) group_count
              FROM civicrm_group_contact gc JOIN civicrm_group g ON gc.group_id = g.id AND gc.status = 'Added'
              WHERE gc.contact_id = %1 AND g.id <> %2 AND g.name LIKE %3";
    $params = array(
      1 => array($contactId, 'Integer'),
      2 => array($defaultLanguageGroupId, 'Integer'),
      3 => array('%'.$languageGroupNameSuffix, 'String'),
    );
    $results = CRM_Core_DAO::executeQuery($query, $params);
    $results->fetch();
    return (bool)$results->group_count;
  }


  /**
   * Delete language group from contact
   *
   * @param $contactId
   * @param $groupId
   */
  public function deleteLanguageGroup($contactId, $groupId) {
    $query = "DELETE FROM civicrm_group_contact
              WHERE contact_id = %1 AND group_id = %2";
    $params = array(
      1 => array($contactId, 'Integer'),
      2 => array($groupId, 'Integer'),
    );
    CRM_Core_DAO::executeQuery($query, $params);
  }


  /**
   * Find activities if activity id is not set up in confirmation link
   *
   * @param $activityId
   * @param $campaignId
   * @param $contactId
   *
   * @return array
   */
  public function findActivitiesIds($activityId, $campaignId, $contactId) {
    $aids = array();
    if (!$activityId && $campaignId) {
      $activityTypeId = CRM_Core_OptionGroup::getValue('activity_type', 'Petition', 'name', 'String', 'value');
      $activityStatusId = CRM_Core_OptionGroup::getValue('activity_status', 'Scheduled', 'name', 'String', 'value');
      $query = "SELECT a.id
                FROM civicrm_activity a JOIN civicrm_activity_contact ac ON a.id = ac.activity_id
                WHERE ac.contact_id = %1 AND activity_type_id = %2 AND a.status_id = %3 AND a.campaign_id = %4";
      $params = array(
        1 => array($contactId, 'Integer'),
        2 => array($activityTypeId, 'Integer'),
        3 => array($activityStatusId, 'Integer'),
        4 => array($campaignId, 'Integer'),
      );
      $results = CRM_Core_DAO::executeQuery($query, $params);
      while ($results->fetch()) {
        $aids[$results->id] = $results->id;
      }
    }
    return $aids;
  }


  /**
   * Set language tag for contact based on language of campaign
   *
   * @param int $contactId
   * @param string $language Language in format en, fr, de, pl etc.
   */
  public function setLanguageTag($contactId, $language) {
    if ($language) {
      $languageTagNamePrefix = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'language_tag_name_prefix');
      $tagName = $languageTagNamePrefix.$language;
      if (!($tagId = $this->getLanguageTagId($tagName))) {
        $tagId = $this->createLanguageTag($tagName);
      }
      if ($tagId) {
        $this->addLanguageTag($contactId, $tagId);
      }
    }
  }


  /**
   * Get language tag id
   *
   * @param $tagName
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  private function getLanguageTagId($tagName) {
    $params = array(
      'sequential' => 1,
      'name' => $tagName,
    );
    $result = civicrm_api3('Tag', 'get', $params);
    if ($result['count'] == 1) {
      return (int)$result['id'];
    }
    return 0;
  }


  /**
   * Create new language tag
   *
   * @param $tagName
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  private function createLanguageTag($tagName) {
    $params = array(
      'sequential' => 1,
      'used_for' => 'civicrm_contact',
      'name' => $tagName,
      'description' => $tagName,
    );
    $result = civicrm_api3('Tag', 'create', $params);
    if ($result['count'] == 1) {
      return (int)$result['id'];
    }
    return 0;
  }


  /**
   * Add tag to contact
   *
   * @param $contactId
   * @param $tagId
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function addLanguageTag($contactId, $tagId) {
    $params = array(
      'sequential' => 1,
      'entity_table' => "civicrm_contact",
      'entity_id' => $contactId,
      'tag_id' => $tagId,
    );
    civicrm_api3('EntityTag', 'create', $params);
  }


  /**
   * Set parameter NO BULK EMAILS (User Opt Out)
   *
   * @param int $contactId
   * @param int $isOptOut
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function setIsOptOut($contactId, $isOptOut) {
    $params = array(
      'sequential' => 1,
      'id' => $contactId,
      'is_opt_out' => $isOptOut,
    );
    civicrm_api3('Contact', 'create', $params);
  }
}
