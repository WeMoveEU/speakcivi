## Setup

Clone this into your extension folder, enable this civicrm extension. and voila.

## Campaign

* Campaign is retrieved by `external_id`
* If campaign doesn't exist in CiviCRM It will be created based on information from Speakout API
  * Language of campaign is determined by name
    * example: Name EN, Name_EN
    * language: en_GB
* Campaign has custom fields
  * `optin message id` - id of message template which will be used in confirmation email
  * `language` - campaign language
  * `sender email` - who is a sender of a email

## Action type

* petition
  * create/get contact, add activity, send confirmation mail
* share
  * create/get contact, add activity

## New contact

SpeakCivi searches contact by primary email.

* If there isn't any contacts, SpeackCivi creates new one (New contact)
* If there is exactly 1 result, SpeakCivi choose this contact (Existing contact)
* If there are more than 1 results, SpeakCivi determine which of them is the most similar (Existing contact)
  * the same first name, last name and primary email
  * the oldest (the smallest id)

### Parameters of contact:

* `created_date` of contact is given from action data
* `contact type`: Individual
* added to group `speakout members` on status `Pending`
* `preferred_language` based on language of campaign
* `source` -> value: `speakout [action_type] [external_id]`
* Do you want to be updated about this and other campaigns?
  * default `YES`
  * If user choose `NO` then:
    * `NO BULK EMAIL` is set up to `TRUE`

### Parameters of activity:

* If `opt_in` = 1 (Default) -> activity status = `Scheduled`
* If `opt_in` = 0 -> activity status = `Completed`
* Do you want to be updated about this and other campaigns?
  * If user choose `NO` then:
    * activity status = `Opt-out`
* Activity type:
  * `Petition` (fill out petition form)
  * `share` (click on button Share on facebook or Share on twitter)
* detail of activity = your comments from petition

### Sending confirmation email

* email content is based on `optin message id`
* email has a link for confirmation with
  * `contact id`
  * `activity id`
  * `campaign id`
  * `hash`

### Click on confirmation url

* If contact has a group on status `Pending` -> change status to `Added`
* If contact doesn't have a group -> add group on status `Added`
* If `activity id` is set up, then
  * If activity has a status `Scheduled` -> change status to `Completed`
* If `campaign id` is set up, then
  * determine country by language
  * change post url into `[country]/post_confirm` in order to present proper language version

## Existing contact

* Add contact to group `speakout member`
* Update address
  * If contact has no address -> add new address
  * If contact has 1 address -> update by new values
  * If contact has more than 1 address ->
    * update similar address by missing value
    * add next if there aren't any similar address

## api endpoint provided
it creates a new route/url http://yourdomain.org/civicrm/speakcivi That should behave like an API from Speakout

I'm a bit confused by the doc, can't find anything about cons_action


      Mechanize.new.post(ENV['API_URL']+'/cons_action', {action_name: internal_name, action_type: rthing.action_type, action_technical_type: "#{ENV['DOMAIN']}:#{rthing.action_type}", external_id: id, create_dt: rthing.created_at, cons_hash: rthing.bsd_cons_hash}.to_json)


    bsd_cons_hash (rsign)
    {firstname: firstname, lastname: lastname, emails: [{ email: email}], addresses: [{zip: postcode}]}

