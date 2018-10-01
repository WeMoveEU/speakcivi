SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED;

INSERT INTO tmp_petition_metrics (activity, campaign_id, npeople) 
SELECT
  'gunique_recipient', cp.parent_id, count(DISTINCT contact_id)
FROM civicrm_mailing m
  JOIN civicrm_mailing_recipients r ON r.mailing_id = m.id
  JOIN civicrm_campaign cp ON cp.id = m.campaign_id
  WHERE m.scheduled_id IS NOT NULL AND m.name NOT LIKE '%-Reminder-%'
GROUP BY parent_id;
