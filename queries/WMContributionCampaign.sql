select m.name mailing, m.id mailing_id, status.name as status, instrument.name as instrument, camp.title as camp, c.* from 
(select count(*) as nb, sum(total_amount) amount, sum(fee_amount) fee,sum(net_amount) net, payment_instrument_id, contribution_status_id status_id, campaign_id, utm_source_30 as utm_source,utm_medium_31 as utm_medium,utm_campaign_33 as utm_campaign 
from civicrm_contribution c 
left join civicrm_value_utm_5 on entity_id=c.id 
where contribution_recur_id is null and is_test=0 and financial_type_id in (1,3) 
group by campaign_id, utm_source_30, utm_medium,utm_campaign, status_id,payment_instrument_id) c
left join civicrm_option_value status on status.option_group_id=11 and status_id=status.value 
left join civicrm_option_value instrument on instrument.option_group_id=10 and payment_instrument_id=instrument.value 
left JOIN civicrm_campaign as camp ON c.campaign_id = camp.id
left join civicrm_mailing m on utm_source like "civimail-%" and m.id = substring(utm_source,10)
order by camp
;

