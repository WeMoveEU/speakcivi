-- Members who:
-- are in speakout member group on status Added (confirmed by email)
-- created during last 1 month
-- have a more than 1 activities (petitions, share)
SELECT
  gc.contact_id, c.display_name, c.created_date,
  count(ac1.id) AS activity_count
FROM civicrm_contact c
  JOIN civicrm_group_contact gc ON c.id = gc.contact_id AND gc.group_id = 42 AND gc.status = 'Added'
  JOIN civicrm_activity_contact ac1 ON gc.contact_id = ac1.contact_id
  JOIN civicrm_activity a1 ON ac1.activity_id = a1.id AND a1.activity_type_id IN (32, 54)
WHERE c.is_opt_out = 0 AND c.created_date >= DATE_ADD(NOW(), INTERVAL -1 MONTH)
GROUP BY gc.contact_id, c.display_name, c.created_date
HAVING count(ac1.id) > 1
ORDER BY c.created_date;
