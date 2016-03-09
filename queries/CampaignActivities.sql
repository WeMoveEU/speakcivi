select 
  campaign_id, c.name, custom.language_4 as language, date(activity_date_time) as date, count(*) as total, 
  sum(case when a.status_id=1 then 1 end) as pending,
  sum(case when a.status_id=2 and activity_type_id=32 then 1 end) as completed,  
  sum(case when a.status_id=4 then 1 end) as optout,  
  sum(case when activity_type_id=54 then 1 end) as share
from civicrm_activity a join civicrm_campaign c on campaign_id=c.id 
join civicrm_value_speakout_integration_2 custom on entity_id=c.id 
where activity_type_id in (32,54) and is_test=0 
group by campaign_id, date 
order by date desc, total desc

-- Having count(*) >1
