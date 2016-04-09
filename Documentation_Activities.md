# Activities and CiviCRM database description
*This is the documentation for the setup used by WeMove.EU. It might not work on other installations. (Definitely the activity type won't be right.)*

## Activity types
1. Petition Signature
2. Share
3. Created a petition
2. Join
3. Leave

### 1 Petition Signature
Subject:= 

status:=



- scheduled
- optout
- completed
- completed new member
(new UK people are also completed new member)


date:= 
duration:=

### 2 Share

### 3 Created a petition

### 4 Join

### 5 Leave

Leave with reason in subject. Subject can be a combination of such reasons:

- is\_opt_out
- do\_not_email
- is_deleted
- is_deceased
- on_hold:1
- on_hold:2

## special Tables for SpeakCivi

? Table for utm_ - Values.


speakcivi\_rsign_source := table for older data until 2016-02-18. 


# Guide to writing SQL on WeMove.EU


## specific numbers for WeMove.EU


### activity_type
- 32	Petition Signature	
- 54	share	
- 55	Created a petition	
- 56	Leave	
- 57	Join	

### activits.status
- 1	Scheduled	Scheduled
- 2	Completed	Completed
- 4	Opt-out	optout
- 9	Completed New Member	optin


## Definition of variables

```mysql
set @share:= 54;
set @signature:=32;
set @created_pet:=55;
set @leave:=56;
set @join:=57;

set @scheduled=1;
set @completed=2;
set @optout=4;
set @completed_new=9; 
set @member_group=42;
```

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


