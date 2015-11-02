-- NEW: user created during last 10 minutes;
SELECT
  activity_date, campaign_id, campaign_name, activity_type_id, activity_type_name, indicator2, COUNT(a.activity_id) AS activity_count
FROM (SELECT
  ac.contact_id, ac.activity_id,
  DATE_FORMAT(a.activity_date_time, '%Y-%m-%d') AS activity_date,
  a.campaign_id,
  CONCAT(cg.name, ' (', IFNULL(cg.external_identifier, '?'), ')') AS campaign_name,
  a.activity_type_id,
  atv.name AS activity_type_name,
  CASE
  WHEN DATE_FORMAT(a.activity_date_time, '%Y%m%d%H%i') - DATE_FORMAT(c.created_date, '%Y%m%d%H%i') < 10
    THEN 'NEW'
  ELSE 'OLD'
  END AS indicator2
FROM civicrm_contact c
  JOIN civicrm_activity_contact ac ON c.id = ac.contact_id
  JOIN civicrm_activity a ON a.id = ac.activity_id
  JOIN civicrm_campaign cg ON a.campaign_id = cg.id
  JOIN civicrm_option_value atv ON atv.option_group_id = 2 AND atv.value = a.activity_type_id
WHERE a.is_deleted = 0 AND a.activity_type_id IN (32, 54)
     ) AS a
GROUP BY activity_date, campaign_id, campaign_name, activity_type_id, activity_type_name, indicator2
ORDER BY activity_date, campaign_name, activity_type_name, indicator2;
