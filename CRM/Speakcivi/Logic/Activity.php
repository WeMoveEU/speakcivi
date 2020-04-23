<?php

class CRM_Speakcivi_Logic_Activity {


  /**
   * Get activity status id. If status isn't exist, create it.
   *
   * @param string $activityStatus Internal name of status
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  public static function getStatusId($activityStatus) {
    $params = array(
      'sequential' => 1,
      'option_group_id' => 'activity_status',
      'name' => $activityStatus,
    );
    $result = civicrm_api3('OptionValue', 'get', $params);
    if ($result['count'] == 0) {
      $params['is_active'] = 1;
      $result = civicrm_api3('OptionValue', 'create', $params);
    }
    return (int)$result['values'][0]['value'];
  }


  /**
   * Create activity for contact.
   *
   * @param $contactId
   * @param $typeId
   * @param $subject
   * @param $campaignId
   * @param $parentActivityId
   * @param $activity_date_time
   * @param $location
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private static function createActivity($contactId, $typeId, $subject = '', $campaignId = 0, $parentActivityId = 0, $activity_date_time = '', $location = '') {
    $params = array(
      'sequential' => 1,
      'activity_type_id' => $typeId,
      'activity_date_time' => date('Y-m-d H:i:s'),
      'status_id' => 'Completed',
      'subject' => $subject,
      'source_contact_id' => $contactId,
    );
    if ($campaignId) {
      $params['campaign_id'] = $campaignId;
    }
    if ($parentActivityId) {
      $params['parent_id'] = $parentActivityId;
    }
    if ($activity_date_time) {
      $params['activity_date_time'] = $activity_date_time;
    }
    if ($location) {
      $params['location'] = $location;
    }
    return civicrm_api3('Activity', 'create', $params);
  }

  /**
   * Set unique activity. Method gets activity by given params and creates only if needed.
   * Need more performance but provide database consistency.
   *
   * @param array $params
   * @param array $ignore parameters to ignore for de-duplication (default value for backward compatibility)
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public static function setActivity($params, $ignore = ['status_id', 'campaign_id']) {
    $contactId = $params['source_contact_id'];
    $getParams = $params;
    foreach ($ignore as $pname) {
      unset($getParams[$pname]);
    }
    $result = civicrm_api3('Activity', 'get', $getParams);

    if ($result['count'] == 0) {
      return civicrm_api3('Activity', 'create', $params);
    } elseif ($result['count'] == 1) {
      return $result;
    } else {
      $activities = $result['values'];
      //Sort by descending date
      usort($activities, function($a1, $a2) { 
        return -strcmp($a1['activity_date_time'], $a2['activity_date_time']); 
      });

      return array(
        'count' => 1,
        'id' => $activities[0]['id'],
        'values' => array(0 => $activities[0]),
      );
    }
  }

  /**
   * Add Self-care activity about switching preferred language.
   *
   * @param int $contactId
   * @param string $fromLanguage
   * @param string $toLanguage
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public static function addLanguageSelfCare($contactId, $fromLanguage, $toLanguage) {
    $typeId = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Self-care');
    $params = [
      'sequential' => 1,
      'activity_type_id' => $typeId,
      'activity_date_time' => date('YmdHis'),
      'status_id' => 'Completed',
      'subject' => sprintf("Language preference switched from %s to %s", $fromLanguage, $toLanguage),
      'source_contact_id' => $contactId,
      'api.ActivityContact.create' => [
        0 => [
          'activity_id' => '$value.id',
          'contact_id' => $contactId,
          'record_type_id' => 3,
        ],
      ],
    ];
    $result = civicrm_api3('Activity', 'create', $params);

    return $result;
  }

  /**
   * Get source fields in custom fields and return as a param array to Activity
   * api action
   *
   * @param $fields
   *
   * @return array
   */
  public static function getSourceFields($fields) {
    $params = [];
    $fields = (array) $fields;
    if (array_key_exists('source', $fields) && $fields['source']) {
      $params[CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_activity_source')] = $fields['source'];
    }
    if (array_key_exists('medium', $fields) && $fields['medium']) {
      $params[CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_activity_medium')] = $fields['medium'];
    }
    if (array_key_exists('campaign', $fields) && $fields['campaign']) {
      $params[CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_activity_campaign')] = $fields['campaign'];
    }

    return $params;
  }

  /**
   * Set source fields in custom fields
   *
   * @param $activityId
   * @param $fields
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function setSourceFields($activityId, $fields) {
    $params = array(
      'sequential' => 1,
      'id' => $activityId,
    );
    $fields = (array)$fields;
    if (array_key_exists('source', $fields) && $fields['source']) {
      $params[CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_activity_source')] = $fields['source'];
    }
    if (array_key_exists('medium', $fields) && $fields['medium']) {
      $params[CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_activity_medium')] = $fields['medium'];
    }
    if (array_key_exists('campaign', $fields) && $fields['campaign']) {
      $params[CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_activity_campaign')] = $fields['campaign'];
    }
    if (count($params) > 2) {
      civicrm_api3('Activity', 'create', $params);
    }
  }

  /**
   * Get share fields in custom fields (medium and tracking code)
   *
   * @param $fields
   *
   * @return mixed
   */
  public static function getShareFields($fields) {
    $params = [];
    $fields = (array) $fields;
    if (array_key_exists('source', $fields) && $fields['source']) {
      $params[CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_tracking_codes_source')] = $fields['source'];
    }
    if (array_key_exists('medium', $fields) && $fields['medium']) {
      $params[CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_tracking_codes_medium')] = $fields['medium'];
    }
    if (array_key_exists('campaign', $fields) && $fields['campaign']) {
      $params[CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_tracking_codes_campaign')] = $fields['campaign'];
    }
    if (array_key_exists('content', $fields) && $fields['content']) {
      $params[CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_tracking_codes_content')] = $fields['content'];
    }

    return $params;
  }


  /**
   * Set share fields in custom fields (medium and tracking code)
   *
   * @param $activityId
   * @param $fields
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function setShareFields($activityId, $fields) {
    $params = array(
      'sequential' => 1,
      'id' => $activityId,
    );
    $fields = (array)$fields;
    if (array_key_exists('source', $fields) && $fields['source']) {
      $params[CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_tracking_codes_source')] = $fields['source'];
    }
    if (array_key_exists('medium', $fields) && $fields['medium']) {
      $params[CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_tracking_codes_medium')] = $fields['medium'];
    }
    if (array_key_exists('campaign', $fields) && $fields['campaign']) {
      $params[CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_tracking_codes_campaign')] = $fields['campaign'];
    }
    if (array_key_exists('content', $fields) && $fields['content']) {
      $params[CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_tracking_codes_content')] = $fields['content'];
    }
    if (count($params) > 2) {
      civicrm_api3('Activity', 'create', $params);
    }
  }

}
