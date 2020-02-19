SELECT
  DATE_FORMAT(d.date,"%Y-%m-%d") as date,
  language,
  SUM(IF(added_date = d.date, number_added, 0)) as nb_joined, 
  SUM(IF(added_date = d.date, -number_removed, 0)) AS nb_left,
  SUM(number_added) - SUM(number_removed) AS nb_members
FROM analytics_member_metrics 
JOIN analytics_kpidates d ON added_date <= d.date
  AND d.date >= "2019-01-01" 
  where language in ('de_DE','en_GB','es_ES','fr_FR','it_IT','pl_PL')

GROUP BY d.date,language
