/* now add speakout_id, speakout_name, language */

SET SQL_SAFE_UPDATES=0;

UPDATE speakeasy_petition_metrics
  JOIN civicrm_campaign AS camp ON camp.id = speakeasy_petition_metrics.campaign_id
  JOIN civicrm_value_speakout_integration_2 speakout_integration ON camp.id = speakout_integration.entity_id
SET
  speakout_id = camp.external_identifier,
  speakout_name = camp.name,
  language = speakout_integration.language_4,
  speakeasy_petition_metrics.parent_id = camp.parent_id
WHERE speakeasy_petition_metrics.campaign_id IS NOT NULL;
