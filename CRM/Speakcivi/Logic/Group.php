<?php

class CRM_Speakcivi_Logic_Group {

  /**
   * Id of "Members by Language" group.
   */
  const PARENT_LANGUAGE_GROUP_ID = 32;

  /**
   * List of language groups in format:
   * key: locale (fr_FR)
   * value: id
   */
  public static function languageGroups() {
    $query = "SELECT source, id FROM civicrm_group WHERE parents = %1 AND source IS NOT NULL";
    $params = [
      1 => [self::PARENT_LANGUAGE_GROUP_ID, 'Integer'],
    ];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $groups = [];
    while ($dao->fetch()) {
      $groups[$dao->source] = $dao->id;
    }

    return $groups;
  }

  /**
   * Remove contact from existing groups.
   *
   * @param int $contactId
   * @param array $groupIds
   */
  public static function remove($contactId, $groupIds) {
    $query = "SELECT group_id 
              FROM civicrm_group_contact 
              WHERE status = 'Added' AND group_id IN (" . implode(', ', $groupIds) .")
                AND contact_id = %1";
    $params = [
      1 => [$contactId, 'Integer'],
    ];
    $existing = [];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    while ($dao->fetch()) {
      $existing[] = $dao->group_id;
    }

    $query = "UPDATE civicrm_group_contact 
              SET status = 'Removed' 
              WHERE contact_id = %1 AND group_id IN (" . implode(', ', $existing) .")";
    CRM_Core_DAO::executeQuery($query, $params);

    $query = "INSERT INTO civicrm_subscription_history (contact_id, group_id, date, method, status) VALUES ";
    $values = [];
    foreach ($existing as $group_id) {
      $values[] = "(" . $contactId . ", " . $group_id . ", NOW(), 'Admin', 'Removed')";
    }
    $query .= implode(', ', $values);
    CRM_Core_DAO::executeQuery($query, $params);
  }

  /**
   * Add contact to group (with subscription history).
   *
   * @param int $contactId
   * @param int $groupId
   */
  public static function add($contactId, $groupId) {
    $query = "SELECT count(id) FROM civicrm_group_contact WHERE contact_id = %1 AND group_id = %2";
    $params = [
      1 => [$contactId, 'Integer'],
      2 => [$groupId, 'Integer'],
    ];
    $cnt = CRM_Core_DAO::singleValueQuery($query, $params);
    if ($cnt) {
      $query = "UPDATE civicrm_group_contact SET status = 'Added' WHERE contact_id = %1 AND group_id = %2";
      CRM_Core_DAO::executeQuery($query, $params);
    }
    else {
      $query = "INSERT INTO civicrm_group_contact (group_id, contact_id, status) VALUES (%2, %1, 'Added')";
      CRM_Core_DAO::executeQuery($query, $params);
    }

    $query = "INSERT INTO civicrm_subscription_history (contact_id, group_id, date, method, status)
              VALUES (%1, %2, NOW(), 'API', 'Added')";
    CRM_Core_DAO::executeQuery($query, $params);
  }

}
