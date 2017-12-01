-- Donations count
INSERT INTO speakeasy_petition_metrics (activity, campaign_id, status, npeople)
  SELECT
    'oneoff_count', campaign_id, currency, count(id) donations_count
  FROM civicrm_contribution
  WHERE contribution_status_id IN (1, 2, 5) AND is_test = 0 AND campaign_id IS NOT NULL
    AND contribution_recur_id IS NULL
  GROUP BY campaign_id, currency;

-- Donations amount
INSERT INTO speakeasy_petition_metrics (activity, campaign_id, status, npeople)
  SELECT
    'oneoff_amount', campaign_id, currency, sum(total_amount)
  FROM civicrm_contribution
  WHERE contribution_status_id IN (1, 2, 5) AND is_test = 0 AND campaign_id IS NOT NULL
    AND contribution_recur_id IS NULL
  GROUP BY campaign_id, currency;

-- Recurring donations
INSERT INTO speakeasy_petition_metrics (activity, campaign_id, status, npeople)
  SELECT
    'recurring_count', c.campaign_id, c.currency, count(c.id)
  FROM civicrm_contribution c
  WHERE c.contribution_status_id IN (1, 2, 5) AND c.is_test = 0 AND c.campaign_id IS NOT NULL
    AND contribution_recur_id IS NOT NULL
  GROUP BY c.campaign_id, c.currency;

-- Recurring donations amount
INSERT INTO speakeasy_petition_metrics (activity, campaign_id, status, npeople)
  SELECT
    'recurring_amount', c.campaign_id, c.currency, sum(c.total_amount)
  FROM civicrm_contribution c
  WHERE c.contribution_status_id IN (1, 2, 5) AND c.is_test = 0 AND c.campaign_id IS NOT NULL
    AND contribution_recur_id IS NOT NULL
  GROUP BY c.campaign_id, c.currency;

-- Number of new members who are today recurring donors
INSERT INTO speakeasy_petition_metrics (activity, campaign_id, npeople)
  SELECT 
    'new_now_recurdonors', a.campaign_id, COUNT(DISTINCT ac.contact_id) 
  FROM civicrm_activity a 
  JOIN civicrm_activity_contact ac ON ac.activity_id=a.id 
  JOIN civicrm_contribution_recur r ON r.contact_id=ac.contact_id 
  WHERE a.status_id=9 AND r.contribution_status_id IN (2, 5)
  GROUP BY a.campaign_id;