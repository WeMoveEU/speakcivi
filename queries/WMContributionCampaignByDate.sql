select 
DATE(receive_date) as date,
max(c.id) as id, currency,count(*) as nb, sum(total_amount) amount, sum(fee_amount) fee,sum(net_amount) net, payment_instrument_id, contribution_status_id status_id, campaign_id, utm_source_30 as utm_source,utm_medium_31 as utm_medium,utm_campaign_33 as utm_campaign
from civicrm_contribution c 
left join civicrm_value_utm_5 on entity_id=c.id 
where contribution_recur_id is null and is_test=0 and financial_type_id in (1,3) 
group by campaign_id, utm_source_30, utm_medium,utm_campaign, status_id,payment_instrument_id,currency
,DATE(receive_date)
order by date DESC,campaign_id DESC;

