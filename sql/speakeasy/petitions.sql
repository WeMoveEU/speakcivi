INSERT INTO tmp_petition_metrics (campaign_id, activity, status, npeople)
  SELECT
    ca.civicrm_camp_id AS civicrm_camp_id,
    LOWER(ca.stand) AS activity,
    ca.status AS status,
    COUNT(*) AS npeople
  FROM
    (SELECT
      civicrm_campaign.id AS civicrm_camp_id,
      civicrm_option_value.name AS stand,
      option_value_status.value AS status,
      c.id
    FROM
      civicrm_contact c
      JOIN civicrm_activity_contact ON civicrm_activity_contact.contact_id = c.id
      JOIN civicrm_activity ON civicrm_activity.id = civicrm_activity_contact.activity_id
      JOIN civicrm_campaign ON civicrm_campaign.id = civicrm_activity.campaign_id
      JOIN civicrm_option_group ON civicrm_option_group.name = 'activity_type'
      JOIN civicrm_option_value ON civicrm_option_value.option_group_id = civicrm_option_group.id
        AND civicrm_activity.activity_type_id = civicrm_option_value.value
      JOIN civicrm_option_group AS option_group_status ON option_group_status.name = 'activity_status'
      JOIN civicrm_option_value AS option_value_status ON option_value_status.option_group_id = option_group_status.id
        AND civicrm_activity.status_id = option_value_status.value
    WHERE
      civicrm_option_value.name IN ('Petition', 'share')) AS ca
  GROUP BY civicrm_camp_id, stand, status;
