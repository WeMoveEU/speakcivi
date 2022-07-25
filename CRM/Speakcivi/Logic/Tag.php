<?php

class CRM_Speakcivi_Logic_Tag {

  /**
   * @param int $contactId
   * @param object $param
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function primarkCustomer($contactId, $param) {
    if (self::isPrimarkCustomer($param)) {
      self::setPrimarkCustomer($contactId);
    }
  }

  /**
   * @param $param
   *
   * @return bool
   */
  private static function isPrimarkCustomer($param) {
    if (
      property_exists($param, 'metadata') &&
      property_exists($param->metadata, 'sign_boolean') &&
      $param->metadata->sign_boolean &&
      strpos(strtolower($param->action_name), '-uber-') !== FALSE
    ) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Set tag to contact.
   *
   * @param int $contactId
   *
   * @return bool
   * @throws \CiviCRM_API3_Exception
   */
  private static function setPrimarkCustomer($contactId) {
    $tagId = self::primarkCustomerId();
    $params = [
      'sequential' => 1,
      'entity_table' => "civicrm_contact",
      'entity_id' => $contactId,
      'tag_id' => $tagId,
    ];
    $result = civicrm_api3('EntityTag', 'get', $params);
    if ($result['count'] == 0) {
      $result = civicrm_api3('EntityTag', 'create', $params);
      return ($result['is_error'] == 0 && $result['total_count'] == 1);
    }

    return (bool) $result['count'];
  }

  /**
   * Get activity type id for Porozumienie o Wolontariacie towarzyszącym
   *
   * @return int|mixed
   * @throws \CiviCRM_API3_Exception
   */
  private static function primarkCustomerId() {
    $key = __CLASS__ . __FUNCTION__;
    $cache = Civi::cache()->get($key);
    if (!isset($cache)) {
      $id = self::addTag('Platform Worker');
      Civi::cache()->set($key, $id);
      return $id;
    }
    return $cache;
  }

  /**
   * Add new tag for contacts.
   *
   * @param string $name
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  private static function addTag($name) {
    if ($name) {
      $params = array(
        'sequential' => 1,
        'name' => $name,
        'used_for' => "Contacts",
      );
      $result = civicrm_api3('Tag', 'get', $params);
      if ($result['count'] == 0) {
        $params['description'] = $name;
        $result = civicrm_api3('Tag', 'create', $params);
      }
      return $result['id'];
    }

    return 0;
  }

}
