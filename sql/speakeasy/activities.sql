# Leave, Join, Bounce, Tweet
INSERT INTO speakeasy_petition_metrics (campaign_id, activity, status, npeople)
  SELECT
    campaign_id, (SELECT LOWER(label) FROM civicrm_option_value WHERE option_group_id = 2 AND value = activity_type_id) activity_type,
    (SELECT label FROM civicrm_option_value WHERE option_group_id = 25 AND value = status_id) status, count(id)
  FROM civicrm_activity a
  WHERE campaign_id IS NOT NULL AND activity_type_id IN (56, 57, 58, 59) AND status_id = 2
  GROUP BY campaign_id, activity_type_id, status_id;
