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
DELETE FROM speakeasy_petition_metrics WHERE need_refresh;
INSERT INTO speakeasy_petition_metrics 
  (speakout_id, campaign_id, speakout_name, speakout_title, language, country, npeople, activity, status, is_opt_out, parent_id, last_updated, need_refresh)
  SELECT speakout_id, campaign_id, speakout_name, speakout_title, language, country, npeople, activity, status, is_opt_out, parent_id, last_updated, need_refresh FROM tmp_petition_metrics;
