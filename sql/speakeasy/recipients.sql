INSERT INTO speakeasy_petition_metrics (activity, campaign_id, npeople)
  SELECT
    'recipient', campaign_id, sum(r.value)
  FROM civicrm_mailing m
    LEFT JOIN data_mailing_counter r ON r.mailing_id = m.id AND r.counter = 'recipients' AND r.timebox = 0
  WHERE m.name NOT LIKE '%-Reminder-%'
  GROUP BY campaign_id;
