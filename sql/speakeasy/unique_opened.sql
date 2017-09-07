INSERT INTO speakeasy_petition_metrics (activity, campaign_id, npeople)
  SELECT
    'unique_opened', m.campaign_id, count(DISTINCT eq.contact_id)
  FROM civicrm_mailing m
    JOIN civicrm_mailing_job mj ON mj.mailing_id = m.id AND mj.is_test = 0 AND m.name NOT LIKE '%-Reminder-%'
    JOIN civicrm_mailing_event_queue eq ON eq.job_id = mj.id
    JOIN civicrm_mailing_event_opened eo ON eo.event_queue_id = eq.id
  WHERE m.campaign_id IS NOT NULL
  GROUP BY m.campaign_id;
