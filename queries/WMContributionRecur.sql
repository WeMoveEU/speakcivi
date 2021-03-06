select 
  R.id,
  co.first_name,
  R.contact_id,
  min(utm_campaign_33) as camp, 
  min(utm_source_30) as sorce,
  min(utm_medium_31) as medium,
  min(utm_content_32) as content,
  create_date as date, 
  R.cancel_date,
  R.currency, 
  pp.name as processor, 
  min(status.name) as status, 
  min(M.status) as sepa_status, 
  frequency_unit as frequency, 
  amount,
  SUM(IF(c.contribution_status_id = 1,1,0)) AS nb,
  SUM(IF(c.contribution_status_id = 1,c.total_amount,0)) AS total_amount,
  min(ctry.iso_code) as country,
  co.preferred_language as lang,
  co.created_date as contact_since,
  min(ab.ab_testing_42) as ab_test,
  min(ab.ab_variant_43) as ab_variant
from civicrm_contribution_recur  as R 
join civicrm_contact co on R.contact_id=co.id 
join civicrm_payment_processor pp on payment_processor_id=pp.id 
left join civicrm_option_value status on option_group_id=11 and contribution_status_id=status.value
left join civicrm_sdd_mandate M on R.id=M.entity_id and M.entity_table="civicrm_contribution_recur" 
left join civicrm_contribution c on contribution_recur_id=R.id
left join civicrm_value_utm_5 utm on c.id=utm.entity_id 
left join civicrm_value_donor_extra_information_3 ab on c.id=ab.entity_id
left join civicrm_address a on a.contact_id=c.contact_id and is_primary=1
left join civicrm_country ctry on a.country_id= ctry.id
where R.is_test=0 and c.is_test=0
group by R.id
order by date desc
limit 10;
