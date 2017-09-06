insert into speakeasy_petition_metrics (activity, campaign_id , npeople) select 'unique_recipient', cp.id, count(Distinct contact_id) from civicrm_mailing m join civicrm_mailing_recipients r on r.mailing_id=m.id and m.name not like "%-Reminder-%" join civicrm_campaign cp on cp.id=m.campaign_id group by campaign_id;

