INSERT INTO speakeasy_petition_metrics (activity, campaign_id, npeople)
  SELECT
    'unsub', campaign_id, sum(r.value)
  FROM civicrm_mailing m
  LEFT JOIN data_mailing_counter r ON r.mailing_id = m.id AND r.counter = 'unsubs' AND r.timebox = 14400
  GROUP BY campaign_id;
