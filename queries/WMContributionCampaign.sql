select m.name mailing, m.id mailing_id, 
status.name as status, instrument.name as instrument, 
camp.id as campaign_id, camp.parent_id as parent_id, camp.title as camp, cc.language_4 as lang, 
c.*,
r.value AS recipients, 
known_signs.value AS signatures 
 from 
(select min(c.id) as id, currency,count(*) as nb, sum(total_amount) amount, sum(fee_amount) fee,sum(net_amount) net, payment_instrument_id, contribution_status_id status_id, campaign_id, utm_source_30 as utm_source,utm_medium_31 as utm_medium,utm_campaign_33 as utm_campaign 
from civicrm_contribution c 
left join civicrm_value_utm_5 on entity_id=c.id 
where contribution_recur_id is null and is_test=0 and financial_type_id in (1,3) 
group by campaign_id, utm_source_30, utm_medium,utm_campaign, status_id,payment_instrument_id,currency) c
left join civicrm_option_value status on status.option_group_id=11 and status_id=status.value 
left join civicrm_option_value instrument on instrument.option_group_id=10 and payment_instrument_id=instrument.value 
left JOIN civicrm_campaign as camp ON c.campaign_id = camp.id
left join  civicrm_value_speakout_integration_2 cc on cc.entity_id=c.campaign_id 
left join civicrm_mailing m on utm_source like "civimail-%" and m.id = substring(utm_source,10)
LEFT JOIN data_mailing_counter r ON r.mailing_id=m.id AND r.counter='recipients' AND r.timebox=0
LEFT JOIN data_mailing_counter known_signs ON known_signs.mailing_id=m.id AND known_signs.counter='known_direct_signs' AND known_signs.timebox=144000
order by camp
;

