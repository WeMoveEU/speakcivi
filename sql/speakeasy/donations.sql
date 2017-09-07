-- Donations count
INSERT INTO speakeasy_petition_metrics (campaign_id, activity, npeople)
  SELECT
    campaign_id, 'Donations count', count(id) donations_count
  FROM civicrm_contribution
  WHERE contribution_status_id = 1 AND is_test = 0 AND campaign_id IS NOT NULL
  GROUP BY campaign_id;

-- Donations amount
INSERT INTO speakeasy_petition_metrics (campaign_id, activity, npeople)
  SELECT
    campaign_id, 'Donations amount', sum(total_amount)
  FROM civicrm_contribution
  WHERE contribution_status_id = 1 AND is_test = 0 AND campaign_id IS NOT NULL
  GROUP BY campaign_id;
