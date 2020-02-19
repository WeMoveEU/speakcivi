select name, 
  campaign_id,
  total,
  new_member,
  existing_member,
  pending,
  optout,
  member_leave,
  (new_member- coalesce(member_leave,0) ) / total *100 as growth,
  case when campaign_type_id=4 then 'wemove' when campaign_type_id=6 then 'youmove'  end as type,
  campaign_type_id
  

from (
select
  campaign_id,
  sum(case when activity_type_id=32 then 1 end) as total,
  sum(case when a.status_id=2 and activity_type_id=32 then 1 end) as existing_member,
  sum(case when a.status_id=1 and activity_type_id=32 then 1 end) as pending,
  sum(case when a.status_id=9 and activity_type_id=32 then 1 end) as new_member,
  sum(case when a.status_id=4 and activity_type_id=32 then 1 end) as optout,
  sum(case when activity_type_id=56 then 1 end) as member_leave
from civicrm_activity a 
join civicrm_campaign c on campaign_id=c.id
where activity_type_id in (32,56) and is_test=0
and c.created_date > '2019-01-01'
group by campaign_id
order by new_member desc
) d
join civicrm_campaign c on campaign_id=c.id
join civicrm_value_speakout_integration_2 custom on entity_id=campaign_id

where total > 10
order by growth desc
;
