select *,
(100*ab_sign/ab_recipient) as ratio_ab

from (
select mailing_a.scheduled_date as date,
m.id, m.name, m.status,
group_percentage,
ra.value+ rb.value+coalesce(rc.value,0) as recipient, 
ra.value+ rb.value as ab_recipient, 
(coalesce(ksa.value, 0) + coalesce(nsa.value, 0) + coalesce(osa.value, 0) + coalesce(psa.value, 0)
+coalesce(ksb.value, 0) + coalesce(nsb.value, 0) + coalesce(osb.value, 0) + coalesce(psb.value, 0)
) AS ab_sign,
TIMESTAMPDIFF(MINUTE,sent_median_a.value,sent_median_b.value) as delta_min_ab,
mailing_a.subject as subject_a,
mailing_b.subject as subject_b

from civicrm_mailing_abtest m 
LEFT JOIN data_mailing_counter ra ON ra.mailing_id=mailing_id_a AND ra.counter='recipients' AND ra.timebox=0 
LEFT JOIN data_mailing_counter rb ON rb.mailing_id=mailing_id_b AND rb.counter='recipients' AND rb.timebox=0 
LEFT JOIN data_mailing_counter rc ON rc.mailing_id=mailing_id_c AND rc.counter='recipients' AND rc.timebox=0 

LEFT JOIN data_mailing_counter ksa ON ksa.mailing_id=mailing_id_a AND ksa.counter='known_direct_signs' AND ksa.timebox=1440
LEFT JOIN data_mailing_counter ksb ON ksb.mailing_id=mailing_id_b AND ksb.counter='known_direct_signs' AND ksb.timebox=1440

LEFT JOIN data_mailing_counter nsa ON nsa.mailing_id=mailing_id_a AND nsa.counter='new_direct_signs' AND nsa.timebox=1440
LEFT JOIN data_mailing_counter nsb ON nsb.mailing_id=mailing_id_b AND nsb.counter='new_direct_signs' AND nsb.timebox=1440

LEFT JOIN data_mailing_counter osa ON osa.mailing_id=mailing_id_a AND osa.counter='optout_direct_signs' AND osa.timebox=1440
LEFT JOIN data_mailing_counter osb ON osb.mailing_id=mailing_id_b AND osb.counter='optout_direct_signs' AND osb.timebox=1440

LEFT JOIN data_mailing_counter psa ON psa.mailing_id=mailing_id_a AND psa.counter='pending_direct_signs' AND psa.timebox=1440
LEFT JOIN data_mailing_counter psb ON psb.mailing_id=mailing_id_b AND psb.counter='pending_direct_signs' AND psb.timebox=1440

LEFT JOIN civicrm_mailing mailing_a ON mailing_a.id=mailing_id_a
LEFT JOIN civicrm_mailing mailing_b ON mailing_b.id=mailing_id_b
LEFT JOIN civicrm_mailing mailing_c ON mailing_c.id=mailing_id_c

LEFT JOIN analytics_mailing_counter_datetime sent_median_a ON sent_median_a.mailing_id=mailing_id_a AND sent_median_a.counter='median_original'
LEFT JOIN analytics_mailing_counter_datetime sent_median_b ON sent_median_b.mailing_id=mailing_id_b AND sent_median_b.counter='median_original'

WHERE status != "Draft" AND m.name like "%launch-INT-EN"
ORDER BY m.id DESC
) as t
where (100*ab_sign/ab_recipient) > 2 and recipient > 80000
order by ratio_ab desc;
