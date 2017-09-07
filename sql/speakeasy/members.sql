INSERT INTO speakeasy_petition_metrics
(campaign_id, activity, is_opt_out, npeople)
  SELECT
    campaign_id AS campaign_id,
    status AS activity,
    is_opt_out AS is_opt_out,
    COUNT(*) AS npeople
  FROM
    civicrm_contact c
    JOIN civicrm_group_contact g ON g.contact_id = c.id AND g.group_id = 42 AND c.is_deleted = 0
    JOIN (SELECT
            CONCAT('speakout petition ', CAST(camp.external_identifier AS CHAR(10))) COLLATE utf8_unicode_ci AS source_string,
            camp.external_identifier AS speakout_id,
            camp.name AS name,
            camp.title AS title,
            camp.id AS campaign_id
          FROM
            civicrm_campaign AS camp) AS kampagne ON kampagne.source_string = c.source
  GROUP BY campaign_id, status, is_opt_out;
