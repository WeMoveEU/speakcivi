INSERT INTO tmp_petition_metrics (activity, campaign_id, npeople)
  SELECT
    'effective_share', a.campaign_id, count(a.id)
  FROM civicrm_activity a
    JOIN civicrm_value_share_params_6 v ON v.entity_id = a.id AND v.signatures_46 IS NOT NULL
  WHERE a.activity_type_id = 54 AND a.status_id = 2
  GROUP BY a.campaign_id;
