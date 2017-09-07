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
