select id, name, status, 
ra.value+ rb.value+ rc.value as recipient, 
oa.value+ ob.value+ oc.value as open, 



 ra.value as recipient_a, rb.value as recipient_b, rc.value as recipient_c,
 oa.value as open_a, ob.value as open_b, oc.value as open_c 

from civicrm_mailing_abtest m 
LEFT JOIN data_mailing_counter ra ON ra.mailing_id=mailing_id_a AND ra.counter='recipients' AND ra.timebox=0 
LEFT JOIN data_mailing_counter rb ON rb.mailing_id=mailing_id_b AND rb.counter='recipients' AND rb.timebox=0 
LEFT JOIN data_mailing_counter rc ON rc.mailing_id=mailing_id_c AND rc.counter='recipients' AND rc.timebox=0 

LEFT JOIN data_mailing_counter oa ON oa.mailing_id=mailing_id_a AND oa.counter='opens' AND oa.timebox=144000
LEFT JOIN data_mailing_counter ob ON ob.mailing_id=mailing_id_b AND ob.counter='opens' AND ob.timebox=144000
LEFT JOIN data_mailing_counter oc ON oc.mailing_id=mailing_id_c AND oc.counter='opens' AND oc.timebox=144000


where status != "Draft" order by id desc limit 10 ;

/*
SELECT m.id, m.name, subject, scheduled_date as date, m.created_id as owner_id, campaign_id, camp.parent_id as parent_campaign_id,
  camp.title as campaign, camp.external_identifier, campaign_type_id, language_4 as lang,
  createdContact.first_name AS owner,
  r.value AS recipients,
  o.timebox,
  o.value AS open,
  c.value AS click,
  u.value AS unsub,
  (coalesce(new_signs.value, 0) + coalesce(known_signs.value, 0) + coalesce(optout_signs.value, 0) + coalesce(pending_signs.value, 0)) AS sign,
  shares.value AS share,
  (coalesce(new_vsigns.value, 0) + coalesce(known_vsigns.value, 0) + coalesce(optout_vsigns.value, 0) + coalesce(pending_vsigns.value, 0)) AS viral_sign,
  vshares.value AS viral_share,
  (coalesce(new_signs.value, 0) + coalesce(new_vsigns.value, 0)) AS new_member,
  received_median.value as received_median,
  sent_median.value as sent_median,
  dr_count.value AS nb_recur,
  do_count.value AS nb_oneoff,
  dr_amount.value AS amount_recur,
  do_amount.value AS amount_oneoff,
  IF(dr_amount.value IS NULL, false, true) as recur,
  (do_amount.value+ dr_amount.value) AS total_amount,
  (do_count.value+ dr_count.value) AS nb_donations,
  d.currency,
  is_completed,
  r.last_updated
FROM civicrm_mailing as m
LEFT JOIN civicrm_campaign as camp ON m.campaign_id = camp.id
LEFT JOIN civicrm_value_speakout_integration_2 camp2 ON camp2.entity_id=camp.id
LEFT JOIN civicrm_contact createdContact ON ( m.created_id = createdContact.id )
LEFT JOIN data_mailing_counter r ON r.mailing_id=m.id AND r.counter='recipients' AND r.timebox=0
LEFT JOIN data_mailing_counter o ON o.mailing_id=m.id AND o.counter='opens'
LEFT JOIN data_mailing_counter c ON c.mailing_id=m.id AND c.counter='clicks' AND c.timebox=o.timebox
LEFT JOIN data_mailing_counter u ON u.mailing_id=m.id AND u.counter='unsubs' AND u.timebox=o.timebox
LEFT JOIN data_mailing_counter new_signs ON new_signs.mailing_id=m.id AND new_signs.counter='new_direct_signs' AND new_signs.timebox=o.timebox
LEFT JOIN data_mailing_counter known_signs ON known_signs.mailing_id=m.id AND known_signs.counter='known_direct_signs' AND known_signs.timebox=o.timebox
LEFT JOIN data_mailing_counter optout_signs ON optout_signs.mailing_id=m.id AND optout_signs.counter='optout_direct_signs' AND optout_signs.timebox=o.timebox
LEFT JOIN data_mailing_counter pending_signs ON pending_signs.mailing_id=m.id AND pending_signs.counter='pending_direct_signs' AND pending_signs.timebox=o.timebox
LEFT JOIN data_mailing_counter shares ON shares.mailing_id=m.id AND shares.counter='direct_shares' AND shares.timebox=o.timebox
LEFT JOIN data_mailing_counter new_vsigns ON new_vsigns.mailing_id=m.id AND new_vsigns.counter='new_viral_signs' AND new_vsigns.timebox=o.timebox
LEFT JOIN data_mailing_counter known_vsigns ON known_vsigns.mailing_id=m.id AND known_vsigns.counter='known_viral_signs' AND known_vsigns.timebox=o.timebox
LEFT JOIN data_mailing_counter optout_vsigns ON optout_vsigns.mailing_id=m.id AND optout_vsigns.counter='optout_viral_signs' AND optout_vsigns.timebox=o.timebox
LEFT JOIN data_mailing_counter pending_vsigns ON pending_vsigns.mailing_id=m.id AND pending_vsigns.counter='pending_viral_signs' AND pending_vsigns.timebox=o.timebox
LEFT JOIN data_mailing_counter vshares ON vshares.mailing_id=m.id AND vshares.counter='viral_shares' AND vshares.timebox=o.timebox
LEFT JOIN data_mailing_counter do_amount ON do_amount.mailing_id=m.id AND do_amount.counter='oneoff_amount' AND do_amount.timebox=o.timebox
LEFT JOIN data_mailing_counter dr_amount ON dr_amount.mailing_id=m.id AND dr_amount.counter='recur_amount' AND dr_amount.timebox=o.timebox
LEFT JOIN data_mailing_counter do_count ON do_count.mailing_id=m.id AND do_count.counter='oneoff_donations' AND do_count.timebox=o.timebox
LEFT JOIN data_mailing_counter dr_count ON dr_count.mailing_id=m.id AND dr_count.counter='recur_donations' AND dr_count.timebox=o.timebox

LEFT JOIN analytics_mailing_counter_datetime received_median ON received_median.mailing_id=m.id AND received_median.counter='median_mailjet'
LEFT JOIN analytics_mailing_counter_datetime sent_median ON sent_median.mailing_id=m.id AND sent_median.counter='median_original'

LEFT JOIN (
  SELECT 
    currency,
    SUBSTRING(utm_source_30, 10) as mailing_id
  FROM civicrm_contribution c JOIN civicrm_value_utm_5 utm ON c.id=entity_id
  WHERE utm_medium_31 in ('email','speakout') AND utm_source_30 LIKE 'civimail-%'
  GROUP BY utm_source_30, currency limit 1
) d ON d.mailing_id=m.id
WHERE scheduled_date is not null;

*/
