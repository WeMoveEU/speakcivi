-- Members who:
-- are in speakout member group (id 42) on status Added (confirmed by email)
-- are not in Staff group (id 5)
-- active during last 1 month
-- have a more than 1 activities from:
-- -- 6 contribution
-- -- 28 survey
-- -- 32 petition
SELECT
  gc.contact_id, c.display_name, c.created_date,
  count(ac1.id) AS activity_count
FROM civicrm_contact c
  JOIN civicrm_group_contact gc ON c.id = gc.contact_id
  JOIN civicrm_activity_contact ac1 ON gc.contact_id = ac1.contact_id
  JOIN civicrm_activity a1 ON ac1.activity_id = a1.id
WHERE c.id NOT IN (SELECT contact_id FROM civicrm_group_contact WHERE group_id = 5 AND status = 'Added')
    AND c.is_opt_out = 0
    AND gc.group_id = 42
    AND gc.status = 'Added'
    AND a1.activity_type_id IN (6, 28, 32)
    AND a1.activity_date_time >= DATE_ADD(NOW(), INTERVAL -1 MONTH)
    AND a1.activity_date_time >= DATE_ADD(c.created_date, INTERVAL 1 DAY)
GROUP BY gc.contact_id, c.display_name, c.created_date
HAVING count(ac1.id) >= 1
ORDER BY c.created_date;


-- total version of above query
SELECT COUNT(t.contact_id)
FROM (
       SELECT
         gc.contact_id
       FROM civicrm_contact c
         JOIN civicrm_group_contact gc ON c.id = gc.contact_id
         JOIN civicrm_activity_contact ac1 ON gc.contact_id = ac1.contact_id
         JOIN civicrm_activity a1 ON ac1.activity_id = a1.id
       WHERE c.id NOT IN (SELECT contact_id FROM civicrm_group_contact WHERE group_id = 5 AND status = 'Added')
           AND c.is_opt_out = 0
           AND gc.group_id = 42
           AND gc.status = 'Added'
           AND a1.activity_type_id IN (6, 28, 32)
           AND a1.activity_date_time >= DATE_ADD(NOW(), INTERVAL -1 MONTH)
           AND a1.activity_date_time >= DATE_ADD(c.created_date, INTERVAL 1 DAY)
       GROUP BY gc.contact_id, c.display_name, c.created_date
       HAVING count(ac1.id) >= 1) t;
