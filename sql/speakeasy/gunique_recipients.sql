SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED;

INSERT INTO tmp_petition_metrics (activity, campaign_id, npeople, need_refresh) 
  SELECT
    'gunique_recipient', cp.parent_id, count(DISTINCT contact_id), 0
  FROM civicrm_mailing m
    JOIN civicrm_mailing_recipients r ON r.mailing_id = m.id
    JOIN civicrm_campaign cp ON cp.id = m.campaign_id
    LEFT JOIN speakeasy_petition_metrics spm ON spm.campaign_id = cp.parent_id AND spm.activity = 'gunique_recipient'
  WHERE m.scheduled_id IS NOT NULL AND m.name NOT LIKE '%-Reminder-%'
    AND (spm.need_refresh OR spm.need_refresh IS NULL)
  GROUP BY parent_id;
