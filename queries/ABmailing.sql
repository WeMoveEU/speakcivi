select 
ab.name,ab.created_date,group_percentage,c.subject,
campaign_type_id, language_4 as lang, 
c.campaign_id,camp.name as campaign,
camp.parent_id,
cu.value AS unsub



from civicrm_mailing_abtest ab 
join civicrm_mailing c on ab.mailing_id_c = c.id 
left join civicrm_campaign camp on camp.id=c.campaign_id 
LEFT JOIN civicrm_value_speakout_integration_2 camp2 ON camp2.entity_id=camp.id

LEFT JOIN data_mailing_counter cu ON cu.mailing_id=c.id AND cu.counter='unsubs' and cu.timebox=14400


where parent_id not in(308) and c.subject is not NULL order by ab.id desc

limit 10;
