SELECT
  ac.contact_id,
  ac.activity_id,
  a.activity_type_id,
  a.campaign_id,
  c.display_name,
  atv.name AS activity_type_name,
  a.status_id,
  c.created_date AS contact_create_date,
  a.activity_date_time,
  camp.external_identifier as speakout_id,
  camp.title as campaign,
  CASE
  WHEN DATE_FORMAT(a.activity_date_time, '%Y%m%d%H%i') - DATE_FORMAT(c.created_date, '%Y%m%d%H%i') < 10
    THEN 'NEW'
  ELSE 'OLD'
  END AS indicator2
FROM civicrm_contact c
  JOIN civicrm_activity_contact ac ON c.id = ac.contact_id
  JOIN civicrm_activity a ON a.id = ac.activity_id
  JOIN civicrm_option_value atv ON atv.option_group_id = 2 AND atv.value = a.activity_type_id
  LEFT JOIN civicrm_campaign camp on a.campaign_id=camp.id
WHERE a.is_deleted = 0 AND a.activity_type_id IN (32, 54)
ORDER BY a.activity_date_time DESC
LIMIT 1000;
