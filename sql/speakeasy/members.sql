INSERT INTO speakeasy_petition_metrics
(campaign_id, activity, is_opt_out, npeople)
  SELECT
    camp.id AS campaign_id,
    status AS activity,
    is_opt_out AS is_opt_out,
    COUNT(*) AS npeople
  FROM
    civicrm_contact c
    JOIN civicrm_group_contact g ON g.contact_id = c.id AND g.group_id = 42 AND c.is_deleted = 0
    JOIN civicrm_campaign AS camp ON camp.external_identifier = substr(c.source, 19)
  WHERE c.source LIKE 'speakout petition %'
  GROUP BY camp.id, status, is_opt_out;
