SELECT m.id, m.name, subject, scheduled_date as date, campaign_id, camp.parent_id as parent_campaign_id,
  camp.title as campaign, camp.external_identifier, language_4 as lang,
  r.value AS recipients,
  o.timebox,
  o.value AS open,
  c.value AS click,
  dr_count.value AS nb_recur,
  dr_amount.value AS amount_recur,
  do_amount.value AS amount_oneoff
FROM civicrm_mailing as m
LEFT JOIN civicrm_campaign as camp ON m.campaign_id = camp.id
LEFT JOIN civicrm_value_speakout_integration_2 camp2 ON camp2.entity_id=camp.id
LEFT JOIN data_mailing_counter r ON r.mailing_id=m.id AND r.counter='recipients' AND r.timebox=0
LEFT JOIN data_mailing_counter o ON o.mailing_id=m.id AND o.counter='opens'
LEFT JOIN data_mailing_counter c ON c.mailing_id=m.id AND c.counter='clicks' AND c.timebox=o.timebox
LEFT JOIN data_mailing_counter do_amount ON do_amount.mailing_id=m.id AND do_amount.counter='oneoff_amount' AND do_amount.timebox=o.timebox
JOIN data_mailing_counter dr_amount ON dr_amount.mailing_id=m.id AND dr_amount.counter='recur_amount' AND dr_amount.timebox=o.timebox
LEFT JOIN data_mailing_counter dr_count ON dr_count.mailing_id=m.id AND dr_count.counter='recur_donations' AND dr_count.timebox=o.timebox
WHERE scheduled_date is not null and o.timebox= 14400
order by m.id desc;
