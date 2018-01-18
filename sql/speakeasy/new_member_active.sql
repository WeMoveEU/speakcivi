-- Number of new members who are active at the moment
INSERT INTO speakeasy_petition_metrics (activity, campaign_id, npeople)
  SELECT 
    'new_active_now', a.campaign_id, COUNT(DISTINCT ac.contact_id) 
  FROM civicrm_activity a 
  JOIN civicrm_activity_contact ac ON ac.activity_id=a.id 
  JOIN civicrm_activity_contact ac_act ON ac.contact_id=ac_act.contact_id
  JOIN civicrm_activity a_act ON a_act.id=ac_act.activity_id
  WHERE a.status_id=9 
    AND a_act.activity_type_id IN (6, 28, 32)
    AND a_act.activity_date_time >= DATE_ADD(a.activity_date_time, INTERVAL 1 DAY)
    AND a_act.activity_date_time >= DATE_ADD(NOW(), INTERVAL -3 MONTH)
  GROUP BY a.campaign_id;

-- Number of new people who were active 3 months later
INSERT INTO speakeasy_petition_metrics (activity, campaign_id, npeople)
  SELECT 
    'new_active_3mlater', a.campaign_id, COUNT(DISTINCT ac.contact_id) 
  FROM civicrm_activity a 
  JOIN civicrm_activity_contact ac ON ac.activity_id=a.id 
  JOIN civicrm_activity_contact ac_act ON ac.contact_id=ac_act.contact_id
  JOIN civicrm_activity a_act ON a_act.id=ac_act.activity_id
  WHERE a.status_id=9 
    AND a_act.activity_type_id IN (6, 28, 32)
    AND (a_act.activity_date_time 
      BETWEEN DATE_ADD(a.activity_date_time, INTERVAL 1 DAY)
          AND DATE_ADD(a.activity_date_time, INTERVAL 3 MONTH))
  GROUP BY a.campaign_id;
