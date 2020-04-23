<?php

function _civicrm_api3_wemove_language_switch_spec(&$spec) {
  $spec['hash'] = [
    'name' => 'hash',
    'title' => ts('Contact hash'),
    'description' => ts('Contact hash'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'api.default' => '',
  ];
  $spec['language'] = [
    'name' => 'language',
    'title' => ts('Language in locale format'),
    'description' => ts('Language in locale format (fr_FR)'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'api.default' => '',
  ];
}

/**
 * Switch the contact to new preferred language:
 * 1. Set preferred_language
 * 2. remove from current language groups
 * 3. add to new language group
 * 4. create "self-care" activity
 *
 * @param $params
 *
 * @return array
 * @throws \CiviCRM_API3_Exception
 */
function civicrm_api3_wemove_language_switch(&$params) {
  $start = microtime(TRUE);
  $hash = $params['hash'];
  $toLanguage = $params['language'];

  $contact = CRM_Speakcivi_Logic_Contact::getContactByHash($hash);
  $contactId = $contact['id'];
  if (!$contactId) {
    return civicrm_api3_create_error('Hash is invalid');
  }

  $fromLanguage = $contact['preferred_language'];
  if (CRM_Speakcivi_Logic_Language::isValid($toLanguage)) {
    CRM_Speakcivi_Logic_Contact::set($contactId, ['preferred_language' => $toLanguage]);
    $languageGroups = CRM_Speakcivi_Logic_Group::languageGroups();
    CRM_Speakcivi_Logic_Group::remove($contactId, $languageGroups);
    CRM_Speakcivi_Logic_Group::add($contactId, $languageGroups[$toLanguage]);
    $result = CRM_Speakcivi_Logic_Activity::addLanguageSelfCare($contactId, $fromLanguage, $toLanguage);

    $values = [
      'contactId' => $contactId,
      'fromLanguage' => $fromLanguage,
      'toLanguage' => $toLanguage,
      'activity_id' => $result['id'],
    ];

    $extraReturnValues = ['time' => microtime(TRUE) - $start];
    return civicrm_api3_create_success($values, $params, 'wemove_language', 'switch', $blank, $extraReturnValues);
  }

  return civicrm_api3_create_error('New language is invalid', [
    'contactId' => $contactId,
    'fromLanguage' => $fromLanguage,
    'toLanguage' => $toLanguage,
  ]);
}
