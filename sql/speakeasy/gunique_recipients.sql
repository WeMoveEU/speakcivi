INSERT INTO speakeasy_petition_metrics (activity, campaign_id, npeople) 
SELECT
  'gunique_recipient', cp.parent_id, count(DISTINCT contact_id)
FROM civicrm_mailing m
  JOIN civicrm_mailing_recipients r ON r.mailing_id = m.id AND m.name NOT LIKE '%-Reminder-%'
  JOIN civicrm_campaign cp ON cp.id = m.campaign_id
GROUP BY parent_id;
