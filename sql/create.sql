
-------
-- Mailing data
-------
DROP TABLE IF EXISTS data_mailing_counter;
CREATE TABLE data_mailing_counter
   (mailing_id INT(10) UNSIGNED, 
    counter VARCHAR(32),
    timebox INT UNSIGNED,
    value INT UNSIGNED,
    last_updated DATETIME NOT NULL,
    PRIMARY KEY (mailing_id, counter, timebox),
    FOREIGN KEY (mailing_id) REFERENCES civicrm_mailing (id));

-- Time boxes in minutes
DROP TABLE IF EXISTS data_timeboxes;
CREATE TABLE data_timeboxes (box INT UNSIGNED, PRIMARY KEY (box));
INSERT INTO data_timeboxes VALUES (60), (180), (360), (720), (1440), (14400);

