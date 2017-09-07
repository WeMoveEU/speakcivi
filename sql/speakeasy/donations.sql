-- Donations count
INSERT INTO speakeasy_petition_metrics (activity, campaign_id, status, npeople)
  SELECT
    'donation', campaign_id, currency, count(id) donations_count
  FROM civicrm_contribution
  WHERE contribution_status_id = 1 AND is_test = 0 AND campaign_id IS NOT NULL
  GROUP BY campaign_id, currency;

-- Donations amount
INSERT INTO speakeasy_petition_metrics (activity, campaign_id, status, npeople)
  SELECT
    'donation_amount', campaign_id, currency, sum(total_amount)
  FROM civicrm_contribution
  WHERE contribution_status_id = 1 AND is_test = 0 AND campaign_id IS NOT NULL
  GROUP BY campaign_id, currency;

-- Recurring donations
INSERT INTO speakeasy_petition_metrics (activity, campaign_id, status, npeople)
  SELECT
    'recurring_donation', c.campaign_id, c.currency, count(c.id)
  FROM civicrm_contribution c
    JOIN civicrm_contribution_recur cr ON cr.id = c.contribution_recur_id AND cr.contribution_status_id IN (1, 2, 5)
  WHERE c.contribution_status_id = 1 AND c.is_test = 0 AND c.campaign_id IS NOT NULL
  GROUP BY c.campaign_id, c.currency;

-- Recurring donations amount
INSERT INTO speakeasy_petition_metrics (activity, campaign_id, status, npeople)
  SELECT
    'recurring_donation', c.campaign_id, c.currency, sum(c.total_amount)
  FROM civicrm_contribution c
    JOIN civicrm_contribution_recur cr ON cr.id = c.contribution_recur_id AND cr.contribution_status_id IN (1, 2, 5)
  WHERE c.contribution_status_id = 1 AND c.is_test = 0 AND c.campaign_id IS NOT NULL
  GROUP BY c.campaign_id, c.currency;
