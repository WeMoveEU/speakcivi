# Activities and CiviCRM database description
*This is the documentation for the setup used by WeMove.EU. It might not work on other installations. (Definitely the activity type won't be right.)*

## Activity types
1. Petition Signature
2. Share
3. Created a petition
4. Join
5. Leave

### 1. Petition Signature
Subject:= 

status:=



- scheduled
- optout
- completed
- completed new member
(new UK people are also completed new member)


date:= 
duration:=

### 2. Share

### 3. Created a petition

### 4. Join

*Join* is added when contact becomes our member. There are several ways how this becomes and this is linked with *subject* of activity:

| Subject | Meaning |
| --- | --- |
| confirmation_link | user clicks on confirmation link in email (confirm, not optout) |
| optIn:0 | UK user signs petition |
| updateBySQL | user is added by cron job (restore from history of Members) |


**CRON JOB Assumptions**

API action `Speakcivi.join` can restore Joins from history of group Members. How this action works:

* adds new Join only if contact doesn't have any Joins
* adds only one and first (the oldest) based on date of history of Members
* set campaign based on contact.source
    * only for contacts created by speakout (source = 'speakout petition %')

On the opposite side is activity *Leave* which means that contact dismisses our membership. One contact can have several Joins but only alternately with *Leave*.

### 5. Leave

*Leave* is added when contact dismisses our membership (and only when had *Join** before). *Leave* has reason in subject. Subject can be a combination of such reasons:

- is\_opt_out
- do\_not_email
- is_deleted
- is_deceased
- on_hold:1
- on_hold:2

*Leave* is created in two cases:

1. when user clicks on optout link in confirmation e-mail
2. when `Speakcivi.leave` cron job remove contact from group Members

## special Tables for SpeakCivi

? Table for utm_ - Values.

civicrm\_value\_speakout\_integration_2

civicrm_value_a2_1, civicrm_value_action_source_4, civicrm_value_donor_extra_information_3



speakcivi\_rsign_source := table for older data until 2016-02-18. 


# Guide to writing SQL on WeMove.EU


## specific numbers for WeMove.EU


### activity_type
- 32	Petition Signature	
- 54	share	
- 55	Created a petition	
- 56	Leave	
- 57	Join	

### activity.status
- 1	Scheduled	Scheduled
- 2	Completed	Completed
- 4	Opt-out	optout
- 9	Completed New Member	optin

### civicrm\_group_contact.status
_(standard civicrm)_

- 'Added'
- 'Pending'
- 'Removed'


## Definition of variables

```sql
-- activity types
SET @share = 54;
SET @signature = 32;
SET @created_pet = 55;
SET @leave = 56;
SET @join = 57;

-- activity statuses:
SET @scheduled = 1;
SET @completed = 2;
SET @optout = 4;
SET @completed_new = 9;

-- groups:
SET @member_group = 42;
```

## Todos

- change Number-of-mails.sql to be run only once per hour. 
- recent_scheduled not confimred.sql : to let campaigners know when confirmation reminders are important. 


## old stuff that needs to be sorted

select v.id, v.value, v.label, v.name from civicrm_option_value v 
join civicrm_option_group g on v.option_group_id = g.id and g.name="activity_type" 
where v.value in (32,54,55,56,57)
;

32	Petition Signature	Petition
54	share	share
55	Created a petition	Created a petition
56	Leave	Leave
57	Join	Join



select v.id, v.value, v.label, v.name from civicrm_option_value v 
join civicrm_option_group g on v.option_group_id = g.id and g.name="activity_status" 
where v.value in (1,2,4,9)
;

1	Scheduled	Scheduled
2	Completed	Completed
4	Opt-out	optout
9	Completed New Member	optin


