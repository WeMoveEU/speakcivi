
# Select limit = 3 für die besten 3 Länder. 

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
primary key(id)
);


