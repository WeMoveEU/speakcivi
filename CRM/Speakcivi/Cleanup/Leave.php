<?php

class CRM_Speakcivi_Cleanup_Leave {
  
  /**
   * Get count of membership in group
   *
   * @param int $groupId Group Id
   *
   * @return int
   */
  public static function getCount($groupId) {
    $query = "SELECT count(DISTINCT c.id)
              FROM civicrm_contact c
                JOIN civicrm_group_contact gc ON c.id = gc.contact_id AND gc.group_id = %1 AND gc.status = 'Added'
                JOIN civicrm_email e ON c.id = e.contact_id AND e.is_primary = 1 AND e.on_hold = 0
              WHERE c.is_deleted = 0 AND c.is_opt_out = 0 AND c.do_not_email = 0 AND c.is_deceased = 0;";
    $params = array(
      1 => array($groupId, 'Integer'),
    );
    return (int)CRM_Core_DAO::singleValueQuery($query, $params);
  }


  /**
   * Clean up membership in group
   *
   * @param int $groupId Group Id
   */
  public static function cleanUp($groupId) {
    $query = "UPDATE civicrm_group_contact gc
              JOIN speakcivi_cleanup_leave i ON gc.contact_id = i.id AND gc.group_id = %1
              SET gc.status = 'Removed'";
    $params = array(
      1 => array($groupId, 'Integer'),
    );
    CRM_Core_DAO::executeQuery($query, $params);

    $query = "INSERT INTO civicrm_subscription_history (contact_id, group_id, date, method, status)
              SELECT DISTINCTROW id, %1, NOW(), 'Admin', 'Removed'
              FROM speakcivi_cleanup_leave";
    $params = array(
      1 => array($groupId, 'Integer'),
    );
    CRM_Core_DAO::executeQuery($query, $params);
  }


  /**
   * Truncate temporary table
   */
  public static function truncateTemporary() {
    $query = "TRUNCATE speakcivi_cleanup_leave";
    CRM_Core_DAO::executeQuery($query);
  }


  /**
   * Load temporary ids for specific group
   *
   * @param int $groupId Group Id
   * @param int $limit Limit
   */
  public static function loadTemporary($groupId, $limit) {
    $limit = (int)$limit;
    if (!$limit) {
      $limit = 100;
    }
    $query = "INSERT IGNORE INTO speakcivi_cleanup_leave
              SELECT id, group_concat(reason ORDER BY reason SEPARATOR ', ') as subject
              FROM (SELECT c.id, 'is_opt_out' AS reason
                FROM civicrm_contact c
                  JOIN civicrm_group_contact gc ON c.id = gc.contact_id AND gc.group_id = %1 AND gc.status = 'Added'
                WHERE c.is_opt_out = 1
                UNION
                SELECT c.id, 'do_not_email' AS reason
                FROM civicrm_contact c
                  JOIN civicrm_group_contact gc ON c.id = gc.contact_id AND gc.group_id = %1 AND gc.status = 'Added'
                WHERE c.do_not_email = 1
                UNION
                SELECT c.id, 'is_deleted' AS reason
                FROM civicrm_contact c
                  JOIN civicrm_group_contact gc ON c.id = gc.contact_id AND gc.group_id = %1 AND gc.status = 'Added'
                WHERE c.is_deleted = 1
                UNION
                SELECT c.id, 'is_deceased' AS reason
                FROM civicrm_contact c
                  JOIN civicrm_group_contact gc ON c.id = gc.contact_id AND gc.group_id = %1 AND gc.status = 'Added'
                WHERE c.is_deceased = 1
                UNION
                SELECT c.id, CONCAT('on_hold:', e.on_hold) AS reason
                FROM civicrm_contact c
                  JOIN civicrm_group_contact gc ON c.id = gc.contact_id AND gc.group_id = %1 AND gc.status = 'Added'
                  JOIN civicrm_email e ON c.id = e.contact_id AND e.on_hold > 0
                UNION
                SELECT c.id, 'no_email' AS reason
                FROM civicrm_contact c
                  JOIN civicrm_group_contact gc ON c.id = gc.contact_id AND gc.group_id = %1 AND gc.status = 'Added'
                  LEFT JOIN civicrm_email e ON c.id = e.contact_id
                WHERE e.id IS NULL
                UNION
                SELECT gc.contact_id id, 'no_consent' reason
                FROM (SELECT contact_id
                      FROM civicrm_group_contact gc1
                      WHERE gc1.group_id = %1 AND gc1.status = 'Added') gc
                  LEFT JOIN civicrm_value_gdpr_temporary_9 gdpr
                    ON gdpr.entity_id = gc.contact_id
                WHERE gdpr.id IS NULL OR gdpr.consent_version_57 IS NULL
              ) t
              GROUP BY t.id
              LIMIT %2";
    $params = array(
      1 => array($groupId, 'Integer'),
      2 => array($limit, 'Integer'),
    );
    CRM_Core_DAO::executeQuery($query, $params);
  }


  /**
   * Count temporary contacts
   *
   * @return int
   */
  public static function countTemporaryContacts() {
    $query = "SELECT count(id) FROM speakcivi_cleanup_leave";
    return (int)CRM_Core_DAO::singleValueQuery($query);
  }


  /**
   * Get data (contact id and subject) for creating activity.
   * Only when contact already has Join.
   *
   * @return array
   */
  public static function getDataForActivities() {
    $query = "SELECT l.id, l.subject, NOW() AS activity_date_time
              FROM speakcivi_cleanup_leave l
              WHERE l.id IN (
                SELECT ac.contact_id
                FROM civicrm_activity a
                  JOIN civicrm_activity_contact ac ON ac.activity_id = a.id
                  JOIN speakcivi_cleanup_leave l2 ON l2.id = ac.contact_id
                WHERE a.activity_type_id = %1 AND a.activity_date_time < (
                  SELECT max(modified_date) FROM civicrm_log WHERE entity_table = 'civicrm_contact' AND entity_id = l2.id
                )
              )";
    $activityTypeId = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'activity_type_join');
    $params = array(1 => array($activityTypeId, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    return $dao->fetchAll();
  }


  /**
   * Create activities in batch
   *
   * @param array $data  Table of contact ids and subjects
   */
  public static function createActivitiesInBatch($data) {
    foreach((array)$data as $contact) {
      CRM_Speakcivi_Logic_Activity::leave($contact['id'], $contact['subject'], 0, 0, $contact['activity_date_time'], 'Added by SpeakCivi API');
    }
  }


  /**
   * Check If contact has more Joins than Leaves
   *
   * @param $contactId
   *
   * @return bool
   */
  public static function hasJoins($contactId) {
    $query = "SELECT
                (SELECT count(DISTINCT a.id)
                FROM civicrm_activity a
                  JOIN civicrm_activity_contact ac ON ac.activity_id = a.id
                WHERE a.activity_type_id = %2 AND ac.contact_id = c.id) -
                    (SELECT count(DISTINCT a.id)
                    FROM civicrm_activity a
                      JOIN civicrm_activity_contact ac ON ac.activity_id = a.id
                    WHERE a.activity_type_id = %3 AND ac.contact_id = c.id) AS cc
              FROM civicrm_contact c
              WHERE c.id = %1;";
    $activityJoinTypeId = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'activity_type_join');
    $activityLeaveTypeId = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'activity_type_leave');
    $params = array(
      1 => array($contactId, 'Integer'),
      2 => array($activityJoinTypeId, 'Integer'),
      3 => array($activityLeaveTypeId, 'Integer'),
    );
    return (bool)CRM_Core_DAO::singleValueQuery($query, $params);
  }
}
