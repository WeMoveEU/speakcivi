
-------
-- Petition metrics
-------
drop table if exists speakeasy_petition_metrics;

create table speakeasy_petition_metrics
(
id int not null auto_increment,
speakout_id int,
campaign_id int,
speakout_name varchar(255), /* TODO: join on campaign instead */
speakout_title varchar (255), /* TODO: join on campaign instead */
language varchar(5), /* TODO: join on campaign instead */
country varchar(255),
npeople int,
activity varchar(255), /* petition signature, share etc. */
status varchar(255), /* completed, scheduled etc. */
is_opt_out tinyint(4), 
parent_id int,
last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
primary key(id)
);


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

