INSERT INTO speakeasy_petition_metrics (activity, campaign_id, npeople)
  SELECT
    'new_member_share', a.campaign_id, count(a.id)
  FROM civicrm_activity a
    JOIN civicrm_value_action_source_4 v ON v.entity_id = a.id
    JOIN civicrm_value_share_params_6 sh ON v.media_28 = sh.utm_medium_38 AND v.campaign_26 = sh.utm_campaign_39
  WHERE a.activity_type_id = 32 AND a.status_id = 9 AND v.source_27 LIKE '%member%'
      AND a.campaign_id IS NOT NULL
  GROUP BY a.campaign_id;
