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
  $language = $params['language'];

  $contactId = CRM_Speakcivi_Logic_Contact::getContactByHash($hash);
  if (CRM_Speakcivi_Logic_Language::isValid($language)) {
    // todo Set preferred_language
    // todo remove from current language groups
    // todo add to new language group
    // todo create "self-care" activity
  }

  $values = [
    $hash,
    $language,
  ];

  $extraReturnValues = ['time' => microtime(TRUE) - $start];
  return civicrm_api3_create_success($values, $params, 'wemove_language', 'switch', $blank, $extraReturnValues);
}
