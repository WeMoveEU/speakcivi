SELECT 
    campaign_id as id,
    parent_id as parent_id,
    speakout_id,
    speakout_title,
    language,
    new_people_signees,
    added AS people_that_actually_came_in,
    added / new_people_signees AS ratio_added,
    pending / new_people_signees AS ratio_pending,
    opt_out / new_people_signees AS ratio_opt_out,
    total_signatures,
    new_people_signees / total_signatures AS ratio_new,
    added / total_signatures AS ratio_actually_added,
    people_who_share / total_signatures AS ratio_share
FROM
    (SELECT 
        campaign_id,
            speakout_title,
            speakout_id,
            language,
            SUM(IF(activity IN ('Added' , 'Pending', 'Removed'), npeople, 0)) AS new_people_signees,
            SUM(IF(activity = 'Added' AND is_opt_out = 0, npeople, 0)) AS added,
            SUM(IF(activity = 'Pending' AND is_opt_out = 0, npeople, 0)) AS pending,
            SUM(IF(activity = 'Removed' AND is_opt_out = 0, npeople, 0)) AS removed,
            SUM(IF(is_opt_out = 1, npeople, 0)) AS opt_out,
            SUM(IF(activity = 'Petition signature', npeople, 0)) AS total_signatures,
            SUM(IF(activity = 'Petition signature'
                AND status = 'Completed', npeople, 0)) AS completed_signatures,
            SUM(IF(activity = 'Petition signature'
                AND status = 'Scheduled', npeople, 0)) AS scheduled_signatures,
            SUM(IF(activity = 'Petition signature'
                AND status = 'Opt-out', npeople, 0)) AS opted_out_signatures,
            SUM(IF(activity = 'share', npeople, 0)) AS people_who_share
    FROM
        analytics_petition_metrics
    GROUP BY campaign_id) AS aggregate
JOIN
  civicrm_campaign c on c.id=campaign_id
ORDER BY campaign_id DESC;

    
    




