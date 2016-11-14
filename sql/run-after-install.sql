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
        LEFT JOIN speakcivi_cleanup_languagegroup_gc gc ON gc.contact_id = lg.id
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

    DELETE FROM speakcivi_cleanup_languagegroup_gc;
    INSERT IGNORE INTO speakcivi_cleanup_languagegroup_gc
      SELECT contact_id
      FROM civicrm_group_contact
      WHERE group_id = groupId AND status = 'Added';


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
    DELETE FROM speakcivi_cleanup_languagegroup;
    RETURN results;
  END#

-- Each english language contact should be in UK or INT subgroup
DROP FUNCTION IF EXISTS speakciviEnglishGroups#
CREATE FUNCTION speakciviEnglishGroups(languageGroupNameSuffix VARCHAR(255)) RETURNS INT
  BEGIN
    DECLARE name_en, name_uk, name_int VARCHAR(255);
    DECLARE id_en, id_uk, id_int, id_country_uk, cid, i INT;
    DECLARE done0 INT DEFAULT FALSE;
    DECLARE cur1_uk CURSOR FOR
      SELECT u.id
      FROM speakcivi_english_uk u LEFT JOIN civicrm_group_contact gc
          ON gc.contact_id = u.id AND gc.group_id = id_uk AND gc.status = 'Added'
      WHERE gc.id IS NULL;
    DECLARE cur1_int CURSOR FOR
      SELECT i.id
      FROM speakcivi_english_int i LEFT JOIN civicrm_group_contact gc
          ON gc.contact_id = i.id AND gc.group_id = id_int AND gc.status = 'Added'
      WHERE gc.id IS NULL;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done0 = 1;

    SET name_en = CONCAT('en', languageGroupNameSuffix COLLATE utf8_unicode_ci);
    SET name_uk = CONCAT(name_en, '-uk');
    SET name_int = CONCAT(name_en, '-int');
    SELECT id INTO id_en FROM civicrm_group WHERE name = name_en COLLATE utf8_unicode_ci;
    SELECT id INTO id_uk FROM civicrm_group WHERE name = name_uk COLLATE utf8_unicode_ci;
    SELECT id INTO id_int FROM civicrm_group WHERE name = name_int COLLATE utf8_unicode_ci;
    SELECT id INTO id_country_uk FROM civicrm_country WHERE iso_code = 'GB';

    DELETE FROM speakcivi_english_uk;
    DELETE FROM speakcivi_english_int;

    -- list of uk english contacts
    INSERT IGNORE INTO speakcivi_english_uk
      SELECT c.id
      FROM civicrm_group_contact gc
        JOIN civicrm_contact c ON c.id = gc.contact_id AND gc.group_id = id_en AND gc.status = 'Added'
        JOIN civicrm_address a ON a.contact_id = c.id AND a.is_primary = 1
      WHERE a.country_id = id_country_uk;

    -- list of int english contacts
    INSERT IGNORE INTO speakcivi_english_int
      SELECT gc.contact_id
      FROM civicrm_group_contact gc
        LEFT JOIN speakcivi_english_uk u ON u.id = gc.contact_id
      WHERE gc.group_id = id_en AND gc.status = 'Added' AND u.id IS NULL;

    -- remove UK
    DELETE FROM speakcivi_english_ids;
    INSERT INTO speakcivi_english_ids
      SELECT g.contact_id
      FROM civicrm_group_contact g
        LEFT JOIN speakcivi_english_uk u ON u.id = g.contact_id
      WHERE g.group_id = id_uk AND g.status = 'Added' AND u.id IS NULL;
    DELETE gc FROM civicrm_group_contact gc
      JOIN speakcivi_english_ids i ON i.id = gc.contact_id AND gc.group_id = id_uk;

    -- remove INT
    DELETE FROM speakcivi_english_ids;
    INSERT INTO speakcivi_english_ids
      SELECT g.contact_id
      FROM civicrm_group_contact g
        LEFT JOIN speakcivi_english_int u ON u.id = g.contact_id
      WHERE g.group_id = id_int AND g.status = 'Added' AND u.id IS NULL;
    DELETE gc FROM civicrm_group_contact gc
      JOIN speakcivi_english_ids i ON i.id = gc.contact_id AND gc.group_id = id_int;

    -- add new to uk
    SET done0 = 0;
    SET i = 0;
    OPEN cur1_uk;
    loop_new_uk: LOOP
      FETCH cur1_uk INTO cid;
      IF done0 THEN
        CLOSE cur1_uk;
        LEAVE loop_new_uk;
      END IF;
      BEGIN
        SET i = i + 1;
        IF (SELECT id FROM civicrm_group_contact WHERE contact_id = cid AND group_id = id_uk) THEN
          UPDATE civicrm_group_contact
          SET status = 'Added'
          WHERE contact_id = cid AND group_id = id_uk;
        ELSE
          INSERT INTO civicrm_group_contact (group_id, contact_id, status)
          VALUES(id_uk, cid, 'Added');
        END IF;
        INSERT INTO civicrm_subscription_history (contact_id, group_id, date, method, status)
        VALUES (cid, id_uk, NOW(), 'Admin', 'Added');
      END;
    END LOOP loop_new_uk;


    -- add new to int
    SET done0 = 0;
    OPEN cur1_int;
    loop_new_int: LOOP
      FETCH cur1_int INTO cid;
      IF done0 THEN
        CLOSE cur1_int;
        LEAVE loop_new_int;
      END IF;
      BEGIN
        SET i = i + 1;
        IF (SELECT id FROM civicrm_group_contact WHERE contact_id = cid AND group_id = id_int) THEN
          UPDATE civicrm_group_contact
          SET status = 'Added'
          WHERE contact_id = cid AND group_id = id_int;
        ELSE
          INSERT INTO civicrm_group_contact (group_id, contact_id, status)
          VALUES(id_int, cid, 'Added');
        END IF;
        INSERT INTO civicrm_subscription_history (contact_id, group_id, date, method, status)
        VALUES (cid, id_int, NOW(), 'Admin', 'Added');
      END;
    END LOOP loop_new_int;

    RETURN i;
  END#

DELIMITER ;
COMMIT ;
