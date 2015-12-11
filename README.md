# Setup

Clone this into your extension folder, enable this civicrm extension. and voila.

# Features
This extension allows an external petition tool to synchronise the action taken there with CiviCRM
The petition tool simply push each action to a rest interface provided by this extension, that then transform them into the entities civicrm knows. 

Campaign->CiviCRM Campaign
Signature->Activity type "petition signature" AND either 
  - create the contact if needed (and do the opt-in "click here to confirm" email)
  - update the contact if needed

The petition tool doesn't have a notion of "unique user" (eg. an id) that they send to the CRM, so we use the email as the unique identifier (ie. each email is a single person). This is obviously not 100% true, but the alternative (several persons can share an email), did create lots of invalid duplicates, because the same person would use different name (eg Bob vs Robert, Maria Lopez Gonzales vs Maria Lopez, Jean Christophe vs Jean-Christophe...). So for now, each email identifies uniquely a person.

## opt-in
If opt-in mode is enabled, Each new contact (new email) has to "opt-in", ie. she will receive an email containing links, and needs to click on one of them. This should happen only onces, ie. once a person has confirmed they want to be contacted, we don't need to ask them every time.

In confirmation email there are two links. First for confirmation the signature with agreement for receiving mailings. Second for only confirmation the signature without agreement (`NO BULK EMAILS` switched on). Each message template for confirmation needs to contain `#CONFIRMATION_BLOCK`.

Email is confirmed if a contact has a group `Members` on status `Added`.

We use a special group `Members` to flag those that have been confirmed (ie. sent an email is "Pending", once clicked on the link is "Added"). __ If the contact is manually removed from that group, she will receive the opt-in email again next time they sign __

## language
Once a contact accepts to be contacted, we need to assign it to one of the languages we use (eg. "french speaking..", "german speaking.."). I can be done manually (eg. everyone that signed a petition for a campaign in french can go to the french speaking group) but would be much easier if done automatically. It doesn't have to be real time, but can be done in batch mode every hour (or daily).

However, few rules:

* a contact should be in only one language group (eg. I shouldn't receive both english and french mailings).
* The latest "specific" (ie. everything but english) language of a petition the member signs is her preferred language
* If she signs a petition in english, the custom field "speak english" is set to true

Language of campaign is determine by `Internal name` in ***speakout***. We use format like this **2015-11-TTIP-ES**, where last `-ES` determine the spanish language.

## gender
It's possible to designate a gender of a user. If speakout petition has a additional field before a first name then user can select his gender. SpeakCivi extension convert those values in gender in CiviCRM. We use such positions: Female, Male and Unspecified.

## prefix
If gender is specified during signing a petition then It could be possible to set up a proper prefix. For females is `Mrs.`, for males is `Mr.` for others is not setting at all. There is only one english language version.

## email greeting
If gender and language is specified then It could be possible to set up a proper email greeting. First of all we have to configure email greeting option groups with special format in description `[locale]:[gender]`

* examples `de_DE:F` stands for german females, `fr_FR:M` stands for french males and `it_IT:` stands for italian unspecified gender
* In spanish version each gender has the same email greeting, so we have only one email greeting type as `es_ES:`

## language group

Each contact in group `Members` supposed to be a member of `LANGUAGE language Activists` group

Available groups:

title | name (internal, not visible from CiviCRM)
--- | ---
German language Activists | de-language-activists
English language Activists | en-language-activists
Spanish language Activists | es-language-activists
French language Activists | fr-language-activists
Italian language Activists | it-language-activists
Polish Language Activists | pl-language-activists
Romanian language Activists | ro-language-activists
Other speaking Activists (default) | other-language-activist

* In SpeakCivi `group` is determined by `name`,
* Only IT team can update `name` value! Remember about this when you will be creating new one or changing names,
* If language can't be determined or there isn't proper group then we use default group,
* On `Speakcivi API Settings` page we have such fields:
  * `Default language group Id`,
  * `Suffix of language group name`,
* Adding to such group is invoked after click on confirmation link (in both versions confirm and optout),
* Contact can have only one language group,
* If contact has already language group current group is not added,
* If Speakcivi can't determine language group, default group is adding to contact,
* Default group is skipping during checking if contact has a language group.

## language tag

Each contact in group `Members` supposed to be a member of `can speak LANGUAGE-SHORTCUT` tag

* `tag` is determined by `name`,
* Format: `can speak SHORTCUT-LANGUAGE` - this is necessary to find out proper tag by shortcut,
* `Speakcivi API Settings` page we have a field `Prefix of language tag name` with default value is `can speak `,
* Examples: `can speak en`, `can speak de`,
* If tag doesn't exist It's creating new one,
* Contact can have many tags (not only one).

# Entities

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
* added to group `Members` on status `Pending`
* `preferred_language` based on language of campaign
* `source` -> value: `speakout [action_type] [external_id]`

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

* There are two types of confirmation:
  * confirm with agreement for mailing
  * confirm without agreement for mailing
    * in this case `NO BULK EMAIL` is set up to `TRUE`
* If contact has a group on status `Pending` -> change status to `Added`
* If contact doesn't have a group -> add group on status `Added`
* If `activity id` is set up, then
  * If activity has a status `Scheduled` -> change status to `Completed`
* If `activity id` is NOT set up and we have a `campaign_id`, then
  * find all activities for this campaign
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
