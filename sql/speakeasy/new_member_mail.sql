INSERT INTO speakeasy_petition_metrics (activity, campaign_id, npeople)
  SELECT
    'new_member_mail', a.campaign_id, count(a.id)
  FROM civicrm_activity a
    JOIN civicrm_value_action_source_4 v ON v.entity_id = a.id
  WHERE a.activity_type_id = 32 AND a.status_id = 9 AND v.source_27 LIKE 'civimail-%'
      AND a.campaign_id IS NOT NULL
  GROUP BY a.campaign_id;
