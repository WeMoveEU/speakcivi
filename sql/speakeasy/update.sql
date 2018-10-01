/* now add speakout_id, speakout_name, language */
UPDATE tmp_petition_metrics
  JOIN civicrm_campaign AS camp ON camp.id = tmp_petition_metrics.campaign_id
  JOIN civicrm_value_speakout_integration_2 speakout_integration ON camp.id = speakout_integration.entity_id
SET
  speakout_id = camp.external_identifier,
  speakout_name = camp.name,
  language = speakout_integration.language_4,
  tmp_petition_metrics.parent_id = camp.parent_id
WHERE tmp_petition_metrics.campaign_id IS NOT NULL AND camp.external_identifier REGEXP '^-?[0-9]+$';

/* Copy latest data to final table */
TRUNCATE speakeasy_petition_metrics;
INSERT INTO speakeasy_petition_metrics SELECT * FROM tmp_petition_metrics;
