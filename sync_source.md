# prepare civi mysql database

To make it easier to process, we'll create a table to contain these sources:

CREATE TABLE `speakcivi_rsign_source` (
     id int unsigned NOT NULL COMMENT 'rsigns.id',
     email varchar(254) COMMENT 'email of the signatory',
     campaign_id int unsigned NOT NULL COMMENT 'speakout campaign id',
     source varchar(254) COMMENT 'utm_source',
     media varchar(254) COMMENT 'utm_media',
     campaign varchar(254) COMMENT 'utm_campaign. nothing to do with the speakout campaign',
     creation_date datetime COMMENT 'signature time', 
     contact_id int unsigned COMMENT 'civicrm contact id',

    PRIMARY KEY ( `id` )
    ,INDEX index_email(email)
    ,INDEX index_campaign(campaign_id)
    ,INDEX index_date(creation_date)
    ,INDEX index_source (source,campaign)

)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;



extract the list of all the signatures' source into a csv

echo "copy (select  distinct on (email,campaign_id) rsigns.id, email, campaign_id, source, medium, campaign, rsigns.created_at::timestamp(0) as created_date  from rsigns join sources on source_id=sources.id)  to STDOUT with CSV delimiter ',' HEADER;" | psql speakout_prod -o /tmp/fullsource.csv


chown mysql /tmp/fullsource.csv
mysql> 
load data infile '/tmp/fullsource.csv' ignore into  table speakcivi_rsign_source FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n' IGNORE 1 LINES (id,email,campaign_id,source,media,campaign,@date) set creation_date=STR_TO_DATE(@date,'%Y-%m-%d %H:%i:%s');

# set the contact_id
update speakcivi_rsign_source s join civicrm_email e on s.email=e.email and e.is_primary=true set s.contact_id=e.contact_id;
# 

 select * from speakcivi_rsign_source s join civicrm_email e on s.email=e.email and e.is_primary=true

update speakcivi_rsign_source d, (select s.id, e.contact_id, min(creation_date) as first_date FROM speakcivi_rsign_source s join civicrm_email e on s.email=e.email and e.is_primary=true group by s.email) src set d.contact_id=src.contact_id where src.id = d.id;

update civicrm_value_a2_1 a join speakcivi_rsign_source d on a.entity_id=d.contact_id set campaign_21=campaign,source_22=source,media_23=media,campaign_id_25=campaign_id;

------


select distinct c.id, s.campaign_id, e.email, s.source, s.media,s.campaign from civicrm_activity a join civicrm_activity_contact ac on activity_id=a.id join civicrm_email e on e.contact_id=ac.contact_id and e.is_primary=true join speakcivi_rsign_source s on s.email=e.email join civicrm_campaign on a.campaign_id=civicrm_campaign.id join civicrm_contact c on ac.contact_id=c.id where civicrm_campaign.external_identifier=s.campaign_id AND activity_type_id=32 and record_type_id=2 and c.source like "speakout petition %" and substring(c.source,19)=s.campaign_id order by s.id limit 13;

update speakcivi_rsign_source join (select distinct c.id, s.campaign_id, s.source, s.media,s.campaign from civicrm_activity a join civicrm_activity_contact ac on activity_id=a.id join civicrm_email e on e.contact_id=ac.contact_id and e.is_primary=true join speakcivi_rsign_source s on s.email=e.email join civicrm_campaign on a.campaign_id=civicrm_campaign.id join civicrm_contact c on ac.contact_id=c.id where civicrm_campaign.external_identifier=s.campaign_id AND activity_type_id=32 and record_type_id=2 and c.source like "speakout petition %" and substring(c.source,19)=s.campaign_id order by s.id limit 13) q set contact_id=q.id;



sudo drush eval "civicrm_initialize( );CRM_Core_DAO::dropTriggers('civicrm_contact');"

insert into civicrm_value_a2_1(entity_id, campaign_id_25, source_22, media_23, campaign_21) (select distinct c.id, s.campaign_id, s.source, s.media,s.campaign from civicrm_activity a join civicrm_activity_contact ac on activity_id=a.id join civicrm_email e on e.contact_id=ac.contact_id and e.is_primary=true join speakcivi_rsign_source s on s.email=e.email join civicrm_campaign on a.campaign_id=civicrm_campaign.id join civicrm_contact c on ac.contact_id=c.id where civicrm_campaign.external_identifier=s.campaign_id AND activity_type_id=32 and record_type_id=2 and c.source like "speakout petition %" and substring(c.source,19)=s.campaign_id order by s.id limit 13)

drush eval "civicrm_initialize( );CRM_Core_DAO::triggerRebuild('civicrm_contact');"

