SELECT
  box,
  CASE WHEN round(box / 60) < 24
    THEN concat(round(box / 60), 'h')
  ELSE concat(round(box / 60 / 24), 'd') END label
FROM data_timeboxes;
