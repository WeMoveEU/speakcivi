-- run this script after installation
START TRANSACTION ;
DELIMITER #
DROP FUNCTION IF EXISTS speakciviUpdateJoinActivities#
CREATE FUNCTION speakciviUpdateJoinActivities(groupId INT, activityType INT, nlimit INT) RETURNS INT
  BEGIN
    DECLARE cid, results INT;
    DECLARE done INT DEFAULT FALSE;
    -- find contacts which are added to group (Members) and which contacts don't have any Joins
    DECLARE cur1 CURSOR FOR
      SELECT DISTINCT sh.contact_id
      FROM civicrm_subscription_history sh
        LEFT JOIN speakcivi_cleanup_join t ON sh.contact_id = t.id
      WHERE group_id = groupId AND sh.status IN ('Added') AND t.id IS NULL
      ORDER BY sh.contact_id
      LIMIT nlimit;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    SET results = 0;

    -- find contacts which already have at least one Join activity
    DELETE FROM speakcivi_cleanup_join;
    INSERT INTO speakcivi_cleanup_join (id)
      SELECT DISTINCT sh.contact_id
      FROM civicrm_subscription_history sh
        JOIN civicrm_activity_contact ac ON sh.contact_id = ac.contact_id AND ac.record_type_id = 2
        JOIN civicrm_activity a ON ac.activity_id = a.id AND a.activity_type_id = activityType AND ABS(TIME_TO_SEC(TIMEDIFF(sh.date, a.activity_date_time))) <= 60
      WHERE sh.group_id = groupId AND sh.status IN ('Added');

    OPEN cur1;
    loop_contacts: LOOP
      FETCH cur1 INTO cid;
      IF done THEN
        CLOSE cur1;
        LEAVE loop_contacts;
      END IF;

      BEGIN
        DECLARE id2, naid, campaignId INT;
        DECLARE aDate DATETIME;
        DECLARE done2 INT DEFAULT FALSE;
        -- find date of Join based on history of group (Members)
        -- only first subscription can have a Join activity
        DECLARE cur2 CURSOR FOR
          SELECT date
          FROM civicrm_subscription_history sh
            LEFT JOIN (SELECT sh.id AS history_id
            FROM civicrm_subscription_history sh
              JOIN civicrm_activity_contact ac ON sh.contact_id = ac.contact_id AND ac.record_type_id = 2
              JOIN civicrm_activity a ON ac.activity_id = a.id AND a.activity_type_id = activityType AND sh.date = a.activity_date_time
            WHERE sh.group_id = groupId AND sh.contact_id = cid AND sh.status IN ('Added')) t ON sh.id = t.history_id
          WHERE group_id = groupId AND contact_id = cid AND sh.status IN ('Added')
          ORDER BY sh.date ASC
          LIMIT 1;
        DECLARE CONTINUE HANDLER FOR NOT FOUND SET done2 = 1;
        OPEN cur2;
        loop_history: LOOP
          FETCH cur2 INTO aDate;
          IF done2 THEN
            CLOSE cur2;
            LEAVE loop_history;
          END IF;

          -- find campaign by source of contact
          -- works only for contacts which were created by speakout
          SELECT ca.id INTO campaignId
          FROM civicrm_contact c
            JOIN civicrm_campaign ca ON concat('speakout petition ', ca.external_identifier) = c.source
          WHERE c.id = cid AND c.source LIKE 'speakout petition %';

          IF campaignId > 0 THEN
            INSERT INTO civicrm_activity (activity_type_id, subject, activity_date_time, status_id, campaign_id)
            VALUES (activityType, 'updateBySQL', aDate, 2, campaignId);
            SET campaignId = 0;
          ELSE
            INSERT INTO civicrm_activity (activity_type_id, subject, activity_date_time, status_id)
            VALUES (activityType, 'updateBySQL', aDate, 2);
          END IF;
          SET naid = last_insert_id();
          INSERT INTO civicrm_activity_contact (activity_id, contact_id, record_type_id) VALUES (naid, cid, 2);
          SET results = results + 1;
        END LOOP loop_history;
      END;
    END LOOP loop_contacts;
    DELETE FROM speakcivi_cleanup_join;
    RETURN results;
  END#


-- If contact is not in Members, remove contact from language groups.
DROP FUNCTION IF EXISTS speakciviRemoveLanguageGroup#
CREATE FUNCTION speakciviRemoveLanguageGroup(groupId INT, languageGroupNameSuffix VARCHAR(255), nLimit INT) RETURNS INT
  BEGIN
    DECLARE cid, gid, results INT;
    DECLARE done1 INT DEFAULT FALSE;
    DECLARE cur1 CURSOR FOR
      SELECT lg.id
      FROM speakcivi_cleanup_languagegroup lg
        LEFT JOIN (SELECT DISTINCT contact_id
        FROM civicrm_group_contact
        WHERE group_id = groupId AND status = 'Added') gc ON gc.contact_id = lg.id
      WHERE gc.contact_id IS NULL
      LIMIT nLimit;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done1 = 1;
    SET results = 0;

    DELETE FROM speakcivi_cleanup_languagegroup;
    INSERT INTO speakcivi_cleanup_languagegroup
      SELECT DISTINCT gc.contact_id
      FROM civicrm_group_contact gc
      WHERE gc.status = 'Added' AND
          gc.group_id IN (SELECT id FROM civicrm_group WHERE name LIKE CONCAT('%', languageGroupNameSuffix COLLATE utf8_unicode_ci))
      ORDER BY gc.contact_id;

    OPEN cur1;
    loop_contacts: LOOP
      FETCH cur1 INTO cid;
      IF done1 THEN
        CLOSE cur1;
        LEAVE loop_contacts;
      END IF;
      BEGIN
        DECLARE done2 INT DEFAULT FALSE;
        DECLARE cur2 CURSOR FOR
          SELECT group_id FROM civicrm_group_contact
          WHERE contact_id = cid AND
              status = 'Added' AND
              group_id IN (SELECT id
              FROM civicrm_group
              WHERE name LIKE CONCAT('%', languageGroupNameSuffix COLLATE utf8_unicode_ci));
        DECLARE CONTINUE HANDLER FOR NOT FOUND SET done2 = 1;
        OPEN cur2;
        loop_groups: LOOP
          FETCH cur2 INTO gid;
          IF done2 THEN
            CLOSE cur2;
            LEAVE loop_groups;
          END IF;
          BEGIN
            INSERT INTO civicrm_subscription_history (contact_id, group_id, date, method, status)
            VALUES (cid, gid, NOW(), 'Admin', 'Removed');
            UPDATE civicrm_group_contact
            SET status = 'Removed'
            WHERE contact_id = cid AND group_id = gid;
          END;
        END LOOP loop_groups;
      END;
      SET results = results + 1;
    END LOOP loop_contacts;
    RETURN results;
  END#

DELIMITER ;
COMMIT ;
