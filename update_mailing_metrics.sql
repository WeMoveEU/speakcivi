-- 
-- These queries assume that they are run at least daily
--

--Recipients
INSERT IGNORE INTO data_mailing_counter
  SELECT mailing_id, 'recipients', 0, COUNT(*) FROM civicrm_mailing_recipients GROUP BY mailing_id;

--Opens
INSERT INTO data_mailing_counter 
  SELECT j.mailing_id, 'opens', b.box, COUNT(DISTINCT q.id) 
    FROM civicrm_mailing_job j
    JOIN civicrm_mailing_event_queue q ON q.job_id=j.id
    JOIN civicrm_mailing_event_opened o ON o.event_queue_id=q.id
    JOIN data_timeboxes b ON TIMESTAMPDIFF(MINUTE, j.start_date, o.time_stamp)<b.box
    WHERE j.is_test=0 AND TIMESTAMPADD(DAY, 100, j.start_date) > NOW()
      AND TIMESTAMPADD(DAY, 1, o.time_stamp) > NOW()
    GROUP BY j.mailing_id, b.box
  ON DUPLICATE KEY UPDATE value=VALUES(value);

--Clicks
INSERT INTO data_mailing_counter 
  SELECT j.mailing_id, 'clicks', b.box, COUNT(DISTINCT q.id) 
    FROM civicrm_mailing_job j
    JOIN civicrm_mailing_event_queue q ON q.job_id=j.id
    JOIN civicrm_mailing_event_trackable_url_open o ON o.event_queue_id=q.id
    JOIN data_timeboxes b ON TIMESTAMPDIFF(MINUTE, j.start_date, o.time_stamp)<b.box
    WHERE j.is_test=0 AND TIMESTAMPADD(DAY, 100, j.start_date) > NOW()
      AND TIMESTAMPADD(DAY, 1, o.time_stamp) > NOW()
    GROUP BY j.mailing_id, b.box
  ON DUPLICATE KEY UPDATE value=VALUES(value);

--Direct activities
--The join on mailing makes sure that it exists (the id is a "user" input)
INSERT INTO data_mailing_counter
  SELECT SUBSTRING(s.source_27, 10), 
      IF(a.activity_type_id=32,
        IF(a.status_id=9, 'new_direct_signs', 'known_direct_signs'), 
        'direct_shares'), 
      b.box, 
      COUNT(DISTINCT c.contact_id) 
    FROM civicrm_activity a 
    JOIN civicrm_activity_contact c on a.id=c.activity_id
    JOIN civicrm_value_action_source_4 s ON a.id=s.entity_id 
    JOIN civicrm_mailing m ON SUBSTRING(s.source_27, 10)=m.id
    JOIN civicrm_mailing_job j ON j.mailing_id=m.id
    JOIN data_timeboxes b ON TIMESTAMPDIFF(MINUTE, j.start_date, a.activity_date_time)<b.box
    WHERE (a.activity_type_id=32 OR a.activity_type_id=54)
      AND s.source_27 LIKE 'civimail-%'
      AND j.is_test=0 AND TIMESTAMPADD(DAY, 100, j.start_date) > NOW()
      AND TIMESTAMPADD(DAY, 1, a.activity_date_time) > NOW()
    GROUP BY s.source_27, a.activity_type_id, b.box
  ON DUPLICATE KEY UPDATE value=VALUES(value);

--Viral activities
--The join on mailing makes sure that it exists (the id is a "user" input)
INSERT INTO data_mailing_counter
  SELECT SUBSTRING(source.source_27, 10), 
      IF(inf_a.activity_type_id=32, 
        IF(inf_a.status_id=9, 'new_viral_signs', 'known_viral_signs'), 
        'viral_shares'), 
      b.box, 
      COUNT(DISTINCT inf_c.contact_id)
    FROM civicrm_value_share_params_6 share 
    JOIN civicrm_value_action_source_4 source ON share.entity_id=source.entity_id AND source.source_27 LIKE 'civimail-%'
    JOIN civicrm_value_action_source_4 infected ON infected.campaign_26=share.utm_campaign_39 AND infected.media_28=share.utm_medium_38
    JOIN civicrm_activity inf_a ON inf_a.id=infected.entity_id
    JOIN civicrm_activity_contact inf_c on inf_a.id=inf_c.activity_id
    JOIN civicrm_mailing m ON SUBSTRING(source.source_27, 10)=m.id
    JOIN civicrm_mailing_job j ON j.mailing_id=m.id
    JOIN data_timeboxes b ON TIMESTAMPDIFF(MINUTE, j.start_date, inf_a.activity_date_time)<b.box
    WHERE j.is_test=0 AND TIMESTAMPADD(DAY, 100, j.start_date) > NOW()
      AND TIMESTAMPADD(DAY, 1, inf_a.activity_date_time) > NOW()
    GROUP BY source.source_27, inf_a.activity_type_id, b.box
  ON DUPLICATE KEY UPDATE value=VALUES(value);

