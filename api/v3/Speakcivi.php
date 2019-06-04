<?php

function civicrm_api3_speakcivi_update_stats($params) {
  $config = CRM_Core_Config::singleton();
  $sql = file_get_contents(dirname(__FILE__) . '/../../sql/update.sql', TRUE);
  CRM_Utils_File::sourceSQLFile($config->dsn, $sql, NULL, TRUE);
  return civicrm_api3_create_success(array("query" => $sql), $params);
}

function _civicrm_api3_speakcivi_sendconfirm_spec(&$params) {
  $params['toEmail']['api.required'] = 1;
  $params['contact_id']['api.required'] = 1;
  $params['campaign_id']['api.required'] = 1;
  $params['from']['api.default'] = html_entity_decode(CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'from'));
  $params['share_utm_source']['api.default'] = 'post-action';
}


function civicrm_api3_speakcivi_sendconfirm($params) {

  $confirmationBlock = $params['confirmation_block'];
  $contactId = $params['contact_id'];
  $campaignId = $params['campaign_id'];
  $activityId = $params['activity_id'];
  if (array_key_exists('no_member', $params)) {
    $noMember = (bool) $params['no_member'];
  }
  else {
    $noMember = FALSE;
  }

  $campaignObj = new CRM_Speakcivi_Logic_Campaign($campaignId);
  $locale = $campaignObj->getLanguage();
  $params['from'] = $campaignObj->getSenderMail();
  $params['format'] = NULL;

  if ($confirmationBlock) {
    $params['subject'] = $campaignObj->getSubjectNew();
    $message = $campaignObj->getMessageNew();
  }
  else {
    $params['subject'] = $campaignObj->getSubjectCurrent();
    $message = $campaignObj->getMessageCurrent();
  }

  if (!$message) {
    if ($confirmationBlock) {
      $message = CRM_Speakcivi_Tools_Dictionary::getMessageNew($locale);
      $campaignObj->setCustomFieldBySQL($campaignId, $campaignObj->fieldMessageNew, $message);
    }
    else {
      // don't send any sharing email
      return civicrm_api3_create_success(1, $params);
    }
  }

  $contact = array();
  $params_contact = array(
    'id' => $contactId,
    'sequential' => 1,
    'return' => ["id", "display_name", "first_name", "last_name", "hash", "email", "email_greeting"],
  );
  $result = civicrm_api3('Contact', 'get', $params_contact);
  if ($result['count'] == 1) {
    $contact = $result['values'][0];
    $contact['checksum'] = CRM_Contact_BAO_Contact_Utils::generateChecksum($contactId, NULL, NULL, $contact['hash']);
  }

  /* CONFIRMATION_BLOCK */
  $hash = sha1(CIVICRM_SITE_KEY . $contactId);
  $utm_content = 'version_' . ($contactId % 2);
  $utm_campaign = $campaignObj->getUtmCampaign();
  if ($noMember) {
    $baseConfirmUrl = 'civicrm/speakcivi/nmconfirm';
    $baseOptoutUrl = 'civicrm/speakcivi/nmoptout';
  }
  else {
    $baseConfirmUrl = 'civicrm/speakcivi/confirm';
    $baseOptoutUrl = 'civicrm/speakcivi/optout';
  }
  $url_confirm_and_keep = CRM_Utils_System::url($baseConfirmUrl,
    "id=$contactId&aid=$activityId&cid=$campaignId&hash=$hash&utm_source=civicrm&utm_medium=email&utm_campaign=$utm_campaign&utm_content=$utm_content", TRUE);
  $url_confirm_and_not_receive = CRM_Utils_System::url($baseOptoutUrl,
    "id=$contactId&aid=$activityId&cid=$campaignId&hash=$hash&utm_source=civicrm&utm_medium=email&utm_campaign=$utm_campaign&utm_content=$utm_content", TRUE);

  $template = CRM_Core_Smarty::singleton();
  $template->assign('url_confirm_and_keep', $url_confirm_and_keep);
  $template->assign('url_confirm_and_not_receive', $url_confirm_and_not_receive);

  /* SHARING_BLOCK */
  $template->assign('url_campaign', $campaignObj->getUrlCampaign());
  $template->assign('url_campaign_encoded', urlencode(extendUrl($campaignObj->getUrlCampaign())));
  $template->assign('url_campaign_fb', prepareCleanUrl(extendUrl($campaignObj->getUrlCampaign())));
  $template->assign('share_url', extendUrl($campaignObj->getUrlCampaign()) . 'action=share&submit&');
  $template->assign('utm_campaign', sha1(CIVICRM_SITE_KEY . $activityId));
  $template->assign('share_utm_source', urlencode($params['share_utm_source']));
  $template->assign('share_email', CRM_Speakcivi_Tools_Dictionary::getShareEmail($locale));
  $template->assign('share_facebook', CRM_Speakcivi_Tools_Dictionary::getShareFacebook($locale));
  $template->assign('share_twitter', CRM_Speakcivi_Tools_Dictionary::getShareTwitter($locale));
  $template->assign('share_whatsapp', CRM_Speakcivi_Tools_Dictionary::getShareWhatsapp($locale));
  $template->assign('twitter_share_text', urlencode($campaignObj->getTwitterShareText()));
  $template->assign('contact', $contact);
  $share_whatsapp_web = $template->fetch('string:' . CRM_Speakcivi_Tools_Dictionary::getShareWhatsappWeb($locale));
  $template->assign('share_whatsapp_web', $share_whatsapp_web);

  /* FETCHING SMARTY TEMPLATES */
  $params['subject'] = $template->fetch('string:' . $params['subject']);
  $locales = getLocale($locale);
  $confirmationBlockHtml = $template->fetch('../templates/CRM/Speakcivi/Page/ConfirmationBlock.' . $locales['html'] . '.html.tpl');
  $confirmationBlockText = $template->fetch('../templates/CRM/Speakcivi/Page/ConfirmationBlock.' . $locales['text'] . '.text.tpl');
  $privacyBlock = $template->fetch('../templates/CRM/Speakcivi/Page/PrivacyBlock.' . $locales['html'] . '.tpl');
  $sharingBlockHtml = $template->fetch('../templates/CRM/Speakcivi/Page/SharingBlock.html.tpl');
  $message = $template->fetch('string:' . $message);

  $messageHtml = str_replace("#CONFIRMATION_BLOCK", $confirmationBlockHtml, $message);
  $messageText = str_replace("#CONFIRMATION_BLOCK", $confirmationBlockText, $message);
  $messageHtml = str_replace("#PRIVACY_BLOCK", $privacyBlock, $messageHtml);
  $messageText = str_replace("#PRIVACY_BLOCK", $privacyBlock, $messageText);
  $messageHtml = str_replace("#SHARING_BLOCK", $sharingBlockHtml, $messageHtml);
  $messageText = str_replace("#SHARING_BLOCK", $sharingBlockHtml, $messageText);

  $params['html'] = html_entity_decode($messageHtml);
  $params['text'] = html_entity_decode(convertHtmlToText($messageText));
  if ($campaignObj->isYoumove()) {
    $params['groupName'] = 'SpeakCivi YouMove';
  }
  else {
    $params['groupName'] = 'SpeakCivi WeMove';
  }
  $params['custom-activity-id'] = $activityId;
  $params['custom-campaign-id'] = $campaignId;
  try {
    $sent = CRM_Utils_Mail::send($params);
    return civicrm_api3_create_success($sent, $params);
  }
  catch (CiviCRM_API3_Exception $exception) {
    $data = array(
      'params' => $params,
      'exception' => $exception,
    );
    return civicrm_api3_create_error('Problem with send email in sendconfirm', $data);
  }
}


function _civicrm_api3_speakcivi_getcount_spec(&$params) {
  $params['group_id']['api.required'] = 1;
  $params['group_id']['api.default'] = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'group_id');
}


function civicrm_api3_speakcivi_getcount($params) {
  $groupId = $params['group_id'];
  $results = CRM_Speakcivi_Cleanup_Leave::getCount($groupId);
  return civicrm_api3_create_success($results, $params);
}


function _civicrm_api3_speakcivi_leave_spec(&$params) {
  $params['limit']['api.required'] = 1;
  $params['limit']['api.default'] = 100;
}


function civicrm_api3_speakcivi_leave($params) {
  $start = microtime(TRUE);
  $tx = new CRM_Core_Transaction();
  try {
    $groupId = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'group_id');
    $limit = $params['limit'];
    CRM_Speakcivi_Cleanup_Leave::truncateTemporary();
    CRM_Speakcivi_Cleanup_Leave::loadTemporary($groupId, $limit);
    CRM_Speakcivi_Cleanup_Leave::cleanUp($groupId);
    $data = CRM_Speakcivi_Cleanup_Leave::getDataForActivities();
    CRM_Speakcivi_Cleanup_Leave::createActivitiesInBatch($data);
    $count = CRM_Speakcivi_Cleanup_Leave::countTemporaryContacts();
    CRM_Speakcivi_Cleanup_Leave::truncateTemporary();
    $tx->commit();
    $results = array(
      'count' => $count,
      'time' => microtime(TRUE) - $start,
    );
    return civicrm_api3_create_success($results, $params);
  }
  catch (Exception $ex) {
    $tx->rollback()->commit();
    throw $ex;
  }
}


function _civicrm_api3_speakcivi_join_spec(&$params) {
  $params['limit']['api.required'] = 1;
  $params['limit']['api.default'] = 1000;
}


function civicrm_api3_speakcivi_join($params) {
  $start = microtime(TRUE);
  $groupId = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'group_id');
  $activityTypeId  = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'activity_type_join');
  $limit = $params['limit'];
  $query = "SELECT speakciviUpdateJoinActivities(%1, %2, %3) AS results;";
  $query_params = array(
    1 => array($groupId, 'Integer'),
    2 => array($activityTypeId, 'Integer'),
    3 => array($limit, 'Integer'),
  );
  $count = (int) CRM_Core_DAO::singleValueQuery($query, $query_params);
  $results = array(
    'count' => $count,
    'time' => microtime(TRUE) - $start,
  );
  return civicrm_api3_create_success($results, $params);
}


function _civicrm_api3_speakcivi_removelanguagegroup_spec(&$params) {
  $params['limit']['api.required'] = 1;
  $params['limit']['api.default'] = 10000;
}


function civicrm_api3_speakcivi_removelanguagegroup($params) {
  $start = microtime(TRUE);
  $groupId = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'group_id');
  $languageGroupNameSuffix = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'language_group_name_suffix');
  $limit = $params['limit'];

  if ($groupId && $languageGroupNameSuffix && $limit) {
    $query = "SELECT speakciviRemoveLanguageGroup(%1, %2, %3) AS results;";
    $query_params = array(
      1 => array($groupId, 'Integer'),
      2 => array($languageGroupNameSuffix, 'String'),
      3 => array($limit, 'Integer'),
    );
    $count = (int) CRM_Core_DAO::singleValueQuery($query, $query_params);
    $results = array(
      'count' => $count,
      'time' => microtime(TRUE) - $start,
    );
    return civicrm_api3_create_success($results, $params);
  }
  else {
    $data = array(
      'groupId' => $groupId,
      'languageGroupNameSuffix' => $languageGroupNameSuffix,
      'limit' => $limit,
    );
    return civicrm_api3_create_error('Not valid params', $data);
  }
}


function _civicrm_api3_speakcivi_fixlanguagegroup_spec(&$params) {
  $params['languages']['api.required'] = 0;
  $params['languages']['api.default'] = NULL;
  $params['limit']['api.required'] = 0;
  $params['limit']['default'] = NULL;
}

/**
 * Find contacts with a preferred language that does not match their language group
 * and move them to the proper group.
 * This assumes that language groups have their 'source' column set with the 
 * matching locale.
 * @param languages: comma-separated list of target languages to limit the search
 * @param limit: Maximum number of contacts to update
 */
function civicrm_api3_speakcivi_fixlanguagegroup($params) { 
  $lang_filter = '1=1';
  if ($params['languages']) {
    $languages = explode(',', $params['languages']);
    $lang_filter = "gl.source IN ('" . implode("', '", $languages) . "')";
  }
  $limit = '';
  if ($params['limit']) {
    $limit = 'LIMIT ' . $params['limit'];
  }
  $groupSuffix = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'language_group_name_suffix');

  $query = "SELECT
      c.id AS contact_id, 
      c.preferred_language AS preferred_language, 
      gl.id AS current_group_id, 
      expected.id AS expected_group_id
    FROM civicrm_group gl 
    STRAIGHT_JOIN civicrm_group_contact gcl ON gcl.group_id=gl.id
    STRAIGHT_JOIN civicrm_contact c 
               ON gcl.contact_id=c.id AND gcl.status='Added' AND gl.source != c.preferred_language
    STRAIGHT_JOIN civicrm_group expected ON expected.source=c.preferred_language
    WHERE $lang_filter
    $limit
  ";

  $count = 0;
  $dao = CRM_Core_DAO::executeQuery($query);
  while ($dao->fetch()) {
    $contactIds = array($dao->contact_id);
    CRM_Contact_BAO_GroupContact::removeContactsFromGroup($contactIds, $dao->current_group_id,
      'Spkcivi', 'Removed', 'fixlanguagegroup');
    CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIds, $dao->expected_group_id,
      'Spkcivi', 'Added', 'fixlanguagegroup');
    $count++;
  }
  return civicrm_api3_create_success(array('count' => $count), $params);
}


function _civicrm_api3_speakcivi_remind_spec(&$spec) {
  $spec['days'] = [
    'name' => 'days',
    'title' => 'How old petitions?',
    'description' => 'How old petitions?',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'api.default' => 1,
  ];
  $spec['days_contact'] = [
    'name' => 'days_contact',
    'title' => 'How old contacts?',
    'description' => 'How old contacts?',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'api.default' => 4,
  ];
  $spec['submit_reminders'] = [
    'name' => 'submit_reminders',
    'title' => 'Submit reminders automatically',
    'description' => 'Submit newly created reminders automatically',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.required' => 1,
    'api.default' => FALSE,
  ];
}


function civicrm_api3_speakcivi_remind($params) {
  // how old not confirmed petitions
  $start = microtime(TRUE);
  $days = $params['days'];
  $daysContact = $params['days_contact'];
  if ($daysContact < $days) {
    $daysContact = $days;
  }
  $groupId = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'group_id');
  $activityTypeId = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Petition');
  $noMemberCampaignType = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'no_member_campaign_type');
  $submitReminders = (bool) $params['submit_reminders'];

  $adminId = 1;

  $query = "SELECT acp.activity_id, ap.campaign_id, acp.contact_id
            FROM civicrm_activity ap
              JOIN civicrm_activity_contact acp ON acp.activity_id = ap.id
              JOIN civicrm_contact c ON c.id = acp.contact_id
              JOIN civicrm_campaign camp ON camp.id = ap.campaign_id
              LEFT JOIN civicrm_group_contact gc ON gc.contact_id = acp.contact_id AND gc.group_id = %1 AND gc.status = 'Added'
            WHERE ap.activity_type_id = %2 AND ap.status_id = 1 AND ap.activity_date_time <= date_add(current_date, INTERVAL -%3 DAY)
                AND c.created_date >= date_add(current_date, INTERVAL -%4 DAY)
                AND c.is_opt_out = 0 AND c.is_deleted = 0 AND c.is_deceased = 0 AND c.do_not_email = 0 AND gc.id IS NULL
                AND camp.is_active = 1";
  $params = array(
    1 => array($groupId, 'Integer'),
    2 => array($activityTypeId, 'Integer'),
    3 => array($days, 'Integer'),
    4 => array($daysContact, 'Integer'),
  );
  $dao = CRM_Core_DAO::executeQuery($query, $params);
  $contacts = array();
  $campaigns = array();
  while ($dao->fetch()) {
    $contacts[$dao->campaign_id][$dao->contact_id] = $dao->contact_id;
    $campaigns[$dao->campaign_id] = $dao->campaign_id;
  }

  $message = array();
  $subject = array();
  $utmCampaign = array();
  $campaignType = array();
  $locale = array();
  $language = array();
  $email = array();
  foreach ($campaigns as $cid) {
    $campaignObj = new CRM_Speakcivi_Logic_Campaign($cid);
    $campaignType[$cid] = $campaignObj->campaign['campaign_type_id'];
    $message[$cid] = $campaignObj->getMessageNew();
    $subject[$cid] = substr(removeSmartyIfClause(convertTokens($campaignObj->getSubjectNew())), 0, 128);
    $utmCampaign[$cid] = $campaignObj->getUtmCampaign();
    $locale[$cid] = $campaignObj->getLanguage();
    $language[$cid] = strtoupper(substr($locale[$cid], 0, 2));
    $email[$cid] = parseSenderEmail($campaignObj->getSenderMail());
  }

  // fetch confirmation block
  $messageHtml = array();
  $messageText = array();
  foreach ($message as $cid => $msg) {
    if ($campaignType[$cid] == $noMemberCampaignType) {
      $baseConfirmUrl = 'civicrm/speakcivi/nmconfirm';
      $baseOptoutUrl = 'civicrm/speakcivi/nmoptout';
    }
    else {
      $baseConfirmUrl = 'civicrm/speakcivi/confirm';
      $baseOptoutUrl = 'civicrm/speakcivi/optout';
    }
    $url_confirm_and_keep = CRM_Utils_System::url($baseConfirmUrl, NULL, TRUE) .
      "?id={contact.contact_id}&cid=$cid&hash={speakcivi.confirmation_hash}&utm_source=reminder&utm_medium=email&utm_campaign=" . $utmCampaign[$cid];
    $url_confirm_and_not_receive = CRM_Utils_System::url($baseOptoutUrl, NULL, TRUE) .
      "?id={contact.contact_id}&cid=$cid&hash={speakcivi.confirmation_hash}&utm_source=reminder&utm_medium=email&utm_campaign=" . $utmCampaign[$cid];
    $locales = getLocale($locale[$cid]);
    $confirmationBlockHtml = implode('', file(dirname(__FILE__) . '/../../templates/CRM/Speakcivi/Page/ConfirmationBlock.' . $locales['html'] . '.html.tpl'));
    $confirmationBlockText = implode('', file(dirname(__FILE__) . '/../../templates/CRM/Speakcivi/Page/ConfirmationBlock.' . $locales['text'] . '.text.tpl'));
    $privacyBlock  = implode('', file(dirname(__FILE__) . '/../../templates/CRM/Speakcivi/Page/PrivacyBlock.' . $locales['html'] . '.tpl'));
    $confirmationBlockHtml = str_replace('{$url_confirm_and_keep}', $url_confirm_and_keep, $confirmationBlockHtml);
    $confirmationBlockHtml = str_replace('{$url_confirm_and_not_receive}', $url_confirm_and_not_receive, $confirmationBlockHtml);
    $confirmationBlockText = str_replace('{$url_confirm_and_keep}', $url_confirm_and_keep, $confirmationBlockText);
    $confirmationBlockText = str_replace('{$url_confirm_and_not_receive}', $url_confirm_and_not_receive, $confirmationBlockText);
    $messageHtml[$cid] = removeSmartyIfClause(convertTokens(removeDelim(str_replace("#CONFIRMATION_BLOCK", $confirmationBlockHtml, $msg))));
    $messageText[$cid] = convertHtmlToText(removeSmartyIfClause(convertTokens(removeDelim(str_replace("#CONFIRMATION_BLOCK", $confirmationBlockText, $msg)))));
    $messageHtml[$cid] = str_replace("#PRIVACY_BLOCK", $privacyBlock, $messageHtml[$cid]);
    $messageText[$cid] = str_replace("#PRIVACY_BLOCK", convertHtmlToText($privacyBlock), $messageText[$cid]);
  }

  foreach ($campaigns as $cid) {
    $sentContacts = findSentContacts($cid);
    $contacts[$cid] = excludeContacts($contacts[$cid], $sentContacts);
    if (is_array($contacts[$cid]) && count($contacts[$cid]) > 0) {
      if ($mailingId = findNotCompletedMailing($cid)) {
        if ($linkedGroupId = findLinkedGroup($mailingId)) {
          addContactsToGroup($contacts[$cid], $linkedGroupId);
        }
        else {
          $includeGroupId = createGroup($cid, $language[$cid]);
          addContactsToGroup($contacts[$cid], $includeGroupId);
          includeGroup($mailingId, $includeGroupId);
        }
      }
      else {
        $name = determineMailingName($cid, $language[$cid]);
        $params = array(
          'name' => $name,
          'subject' => $subject[$cid],
          'body_text' => $messageText[$cid],
          'body_html' => $messageHtml[$cid],
          'created_id' => $adminId,
          'created_date' => date('YmdHis'),
          'campaign_id' => $cid,
          'mailing_type' => 'standalone',
          'unsubscribe_id' => 5,
          'resubscribe_id' => 6,
          'optout_id' => 7,
          'reply_id' => 8,
          'open_tracking' => 1,
          'url_tracking' => 1,
          'dedupe_email' => 1,
          'from_name' => $email[$cid]['from_name'],
          'from_email' => $email[$cid]['from_email'],
          'footer_id' => chooseFooter($language[$cid]),
        );
        $mailing = new CRM_Mailing_BAO_Mailing();
        $mm = $mailing->add($params);

        excludeGroup($mm->id, $groupId);

        if ($existingGroupId = findExistingGroup($cid)) {
          cleanGroup($existingGroupId);
          addContactsToGroup($contacts[$cid], $existingGroupId);
          includeGroup($mm->id, $existingGroupId);
        }
        else {
          $includeGroupId = createGroup($cid, $language[$cid]);
          addContactsToGroup($contacts[$cid], $includeGroupId);
          includeGroup($mm->id, $includeGroupId);
        }
        if ($submitReminders) {
          submitReminder($mm->id);
        }
      }
    }
  }

  $results = array(
    'time' => microtime(TRUE) - $start,
  );
  return civicrm_api3_create_success($results, $params);
}

function _civicrm_api3_speakcivi_englishgroups_spec(&$params) {
  $params['parent_id']['api.required'] = 1;
  $params['uk_id']['api.required'] = 1;
  $params['int_id']['api.required'] = 1;
}

function civicrm_api3_speakcivi_englishgroups($params) {
  $start = microtime(TRUE);
  $languageGroupNameSuffix = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'language_group_name_suffix');

  if ($params['parent_id'] && $params['uk_id'] && $params['int_id']) {
    $query = "SELECT speakciviEnglishGroups(%1, %2, %3) AS results;";
    $query_params = array(
      1 => array($params['parent_id'], 'Integer'),
      2 => array($params['uk_id'], 'Integer'),
      3 => array($params['int_id'], 'Integer'),
    );
    $count = (int) CRM_Core_DAO::singleValueQuery($query, $query_params);
    $results = array(
      'count' => $count,
      'time' => microtime(TRUE) - $start,
    );
    return civicrm_api3_create_success($results, $params);
  }
  else {
    $data = array(
      'languageGroupNameSuffix' => $languageGroupNameSuffix,
    );
    return civicrm_api3_create_error('Not valid params', $data);
  }
}


function _civicrm_api3_speakcivi_get_consents_required_spec(&$spec) {
  $spec['email'] = [
    'name' => 'email',
    'title' => 'Email address of the user',
    'description' => 'Email address of the user',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'api.default' => '',
  ];
  $spec['consent_ids'] = [
    'name' => 'consent_ids',
    'title' => 'List of consent ids',
    'description' => 'List of public ids (consent version + consent language)',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'api.default' => '',
  ];
  $spec['country'] = [
    'name' => 'country',
    'title' => 'Country code of the user',
    'description' => 'Country code of the user',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'api.default' => '',
  ];
}

function civicrm_api3_speakcivi_get_consents_required($params) {
  if (in_array($params['country'], ['de', 'at'])) {
    $result = ['consents_required' => []];
    return civicrm_api3_create_success($result, $params);
  }

  // The inclusion of consent ids in query is not safe, but let's assume we are protected by API key
  $dpaType = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'SLA Acceptance');
  $query = "
      SELECT consent_version active_consent_version
      FROM
        (SELECT
          consent_version, max(completed_date) max_completed_date, max(cancelled_date) max_cancelled_date
        FROM
          (SELECT
            a.subject consent_version,
            if(a.status_id = 2, max(a.activity_date_time), '1970-01-01') completed_date,
            if(a.status_id = 3, max(a.activity_date_time), '1970-01-01') cancelled_date
          FROM civicrm_email e
            JOIN civicrm_activity_contact ac ON ac.contact_id = e.contact_id
            JOIN civicrm_activity a ON a.id = ac.activity_id AND a.activity_type_id = %2
          WHERE e.email = %1 AND e.is_primary = 1
          GROUP BY consent_version, a.status_id) t
        GROUP BY consent_version) t2
      WHERE max_completed_date > max_cancelled_date
		UNION
      SELECT consent_version_57
      FROM civicrm_email e 
      JOIN civicrm_value_gdpr_temporary_9 g ON g.entity_id=e.contact_id
      WHERE e.email = %1 AND e.is_primary = 1
	";
  $queryParams = [
    1 => [$params['email'], 'String'],
    2 => [$dpaType, 'Integer']
  ];
  $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
  $activeConsentVersions = [];
  while ($dao->fetch()) {
    $version = getConsentVersion($dao->active_consent_version);
    $activeConsentVersions[] = $version;
    //If this is a partner consent, add the major version in active versions
    if (strpos($version, '_')) {
      $activeConsentVersions[] = explode('.', $version)[0];
    }
  }

  $requiredConsents = [];
  foreach ($params['consent_ids'] as $consentId) {
    if (!in_array(getConsentVersion($consentId), $activeConsentVersions)) {
      $requiredConsents[] = $consentId;
    }
  }
  $result = ['consents_required' => $requiredConsents];
  return civicrm_api3_create_success($result, $params);
}

function _civicrm_api3_speakcivi_update_campaign_consent_spec(&$spec) {
  $spec['campaign_name'] = [
    'name' => 'campaign_name',
    'title' => 'Campaign internal name',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1
  ];
  $spec['partner_slug'] = [
    'name' => 'partner_slug',
    'title' => 'Partner slug',
    'type' => CRM_Utils_Type::T_STRING,
    'api_required' => 1
  ];
  $spec['partner_name'] = [
    'name' => 'partner_name',
    'title' => 'Partner name',
    'type' => CRM_Utils_Type::T_STRING,
    'api_required' => 1
  ];
  $spec['partner_privacy_url'] = [
    'name' => 'partner_privacy_url',
    'title' => "URL of the partner's privacy policy",
    'type' => CRM_Utils_Type::T_STRING,
    'api_required' => 1
  ];
}

function civicrm_api3_speakcivi_update_campaign_consent($params) {
  $campaigns = civicrm_api3('Campaign', 'get', [ 'name' => $params['campaign_name'], 'return' => 'id' ]);
  if ($campaigns['is_error']) {
    return civicrm_api3_create_error("Error while retrieving {$params['campaign_name']}", $data);
  }
  else {
    $updated_campaigns = [];
    foreach ($campaigns['values'] as $c) {
      $campaign = new CRM_Speakcivi_Logic_Campaign($c['id']);
      $partner = [
        'slug' => $params['partner_slug'],
        'name' => $params['partner_name'],
        'privacy_url' => $params['partner_privacy_url'],
      ];
      $updated_campaigns[$c['id']] = $campaign->update_consent($partner);
    }
    return civicrm_api3_create_success($updated_campaigns, $params);
  }
}

function _civicrm_api3_speakcivi_migrate_gdpr_spec(&$spec) {
  $spec['limit'] = [
    'name' => 'limit',
    'title' => 'Maximum number of contacts to migrate',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'api.default' => 1000,
  ];
}

function civicrm_api3_speakcivi_migrate_gdpr($params) {
  $dpaType = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'SLA Acceptance');
  $limit = $params['limit'];

  $query = "
		SELECT
			g.*
		FROM civicrm_value_gdpr_temporary_9 g
		LEFT JOIN (
			SELECT distinct contact_id
			FROM civicrm_activity_contact ac
			JOIN civicrm_activity a ON a.id=ac.activity_id
			WHERE activity_type_id=$dpaType
		) dpac ON dpac.contact_id=g.entity_id
		WHERE dpac.contact_id IS NULL AND g.consent_version_57 IS NOT NULL
    ORDER BY g.entity_id
		LIMIT $limit
  ";

  $count = 0;
  $first_contact = NULL;
  $last_contact = NULL;
  $dao = CRM_Core_DAO::executeQuery($query);
  while ($dao->fetch()) {
    if (!$first_contact) {
      $first_contact = $dao->entity_id;
    }
    $last_contact = $dao->entity_id;

    $consent = new CRM_Speakcivi_Logic_Consent();
    $consent->createDate = $dao->consent_date_56;
    $consent->version = $dao->consent_version_57;
    $consent->language = $dao->consent_language_62;
    $consent->utmSource = $dao->utm_source_59;
    $consent->utmMedium = $dao->utm_medium_60;
    $consent->utmCampaign = $dao->utm_campaign_61;

    CRM_Speakcivi_Logic_Activity::dpa($consent, $dao->entity_id, $dao->campaign_id_58, 'Completed');
    $count++;
  }

  $result = [
    'count' => $count,
    'first_contact' => $first_contact,
    'last_contact' => $last_contact,
  ];
  return civicrm_api3_create_success($result, $params);
}

/**
 * From a consent id, return the string to compare to previous consent version in order to check its compatibility
 * If the consent id contains a '_' (partner consent), returns the full version (id stripped of language)
 * Otherwise return the major version only
 */
function getConsentVersion($consentId) {
  if (strpos($consentId, '_') === FALSE) {
    $version = explode('.', $consentId)[0];
  } else {
    $version = explode('-', $consentId)[0];
  }
  return $version;
}


/**
 * Get locale version for locale from params. Default is a english version.
 *
 * @param string $locale Locale, so format is xx_YY (language_COUNTRY), ex. en_GB
 *
 * @return array
 */
function getLocale($locale) {
  $localeTab = array(
    'html' => 'en_GB',
    'text' => 'en_GB',
  );
  foreach ($localeTab as $type => $localeType) {
    if (file_exists(dirname(__FILE__) . '/../../templates/CRM/Speakcivi/Page/ConfirmationBlock.' . $locale . '.' . $type . '.tpl')) {
      $localeTab[$type] = $locale;
    }
  }
  return $localeTab;
}


/**
 *
 * @param $html
 *
 * @return string
 */
function convertHtmlToText($html) {
  $html = str_ireplace(array('<br>', '<br/>', '<br />'), "\n", $html);
  $html = strip_tags($html, '<a>');
  $re = '/<a href="(.*)">(.*)<\/a>/';
  if (preg_match_all($re, $html, $matches)) {
    foreach ($matches[0] as $id => $tag) {
      $html = str_replace($tag, $matches[2][$id] . "\n" . str_replace(' ', '+', $matches[1][$id]), $html);
    }
  }
  return $html;
}


/**
 * Prepare clean url for Facebook sharing.
 * @param $url
 *
 * @return string
 */
function prepareCleanUrl($url) {
  $search = array(
    'https://',
    'http://',
  );
  $url = str_replace($search, '', $url);
  return urlencode($url);
}

/**
 * Extend url with valid char for utm params
 *
 * @param string $url
 *
 * @return string
 */
function extendUrl($url) {
  if (strpos($url, '?')) {
    return $url . '&';
  }
  return $url . '?';
}


/**
 * Remove delim code from string (confirmation block).
 * @param string $str
 *
 * @return mixed
 */
function removeDelim($str) {
  $first = strpos($str, '{ldelim}');
  $last = strrpos($str, '{rdelim}');
  if ($first !== FALSE && $last !== FALSE) {
    $str = substr_replace($str, '', $first, $last - $first + 8);
    $str = preg_replace('/<script[^>]*>/i', '', $str);
    $str = preg_replace('/<\/script>/i', '', $str);
  }
  return $str;
}


/**
 * Convert smarty tokens into civicrm format.
 * @param $content
 *
 * @return mixed
 */
function convertTokens($content) {
  return str_replace('{$', '{', $content);
}


/**
 * Simplify whole smarty If clause to text from first block.
 * @param string $str
 *
 * @return string
 */
function removeSmartyIfClause($str) {
  if (strpos($str, '{if ') !== FALSE) {
    // 1. remove block else-endif
    $re = "/\\{else\\}(.*)\\{\\/if\\}/sU";
    $str = preg_replace($re, '{/if}', $str);
    // 2. clean out block if-endif
    $re = "/\\{if[^\\}]*\\}(.*)\\{\\/if\\}/sU";
    if (preg_match_all($re, $str, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $key => $match) {
        $str = str_replace($match[0], $match[1], $str);
      }
    }
  }
  return $str;
}


/**
 * Parse sender email fields as from name and email separately.
 * @param $senderEmail
 *
 * @return array
 */
function parseSenderEmail($senderEmail) {
  $senderEmail = str_replace('>', '&gt;', str_replace('<', '&lt;', $senderEmail));
  $arr = explode('&lt;', $senderEmail);
  return array(
    'from_name' => trim(str_replace('"', '', $arr[0])),
    'from_email' => trim(str_replace('&gt;', '', $arr[1])),
  );
}


/**
 * Find not completed (not scheduled) mailing which can be use.
 * @param int $campaignId
 *
 * @return int
 */
function findNotCompletedMailing($campaignId) {
  $query = "SELECT id
            FROM civicrm_mailing
            WHERE campaign_id = %1 AND name LIKE '%Reminder-__--CAMP-ID-%' AND is_completed IS NULL
            ORDER BY id
            LIMIT 1";
  $params = array(
    1 => array($campaignId, 'Integer'),
  );
  return (int) CRM_Core_DAO::singleValueQuery($query, $params);
}


/**
 * Find contacts which already received reminder mailing for this campaign.
 * @param int $campaignId
 *
 * @return array
 */
function findSentContacts($campaignId) {
  $query = "SELECT eq.contact_id
            FROM civicrm_mailing m
              JOIN civicrm_mailing_job mj ON mj.mailing_id = m.id
              JOIN civicrm_mailing_event_queue eq ON eq.job_id = mj.id
            WHERE m.campaign_id = %1 AND m.name LIKE '%Reminder-__--CAMP-ID-%'";
  $params = array(
    1 => array($campaignId, 'Integer'),
  );
  $dao = CRM_Core_DAO::executeQuery($query, $params);
  $contacts = array();
  while ($dao->fetch()) {
    $contacts[$dao->contact_id] = $dao->contact_id;
  }
  return $contacts;
}


/**
 * Exclude contacts from base.
 * @param array $base
 * @param array $exclude
 *
 * @return array
 */
function excludeContacts($base, $exclude) {
  foreach ($base as $baseContact) {
    if (array_key_exists($baseContact, $exclude)) {
      unset($base[$baseContact]);
    }
  }
  return $base;
}


/**
 * Find group used for existing mailing.
 * @param int $mailingId
 *
 * @return int
 */
function findLinkedGroup($mailingId) {
  $query = "SELECT entity_id
            FROM civicrm_mailing_group
            WHERE mailing_id = %1 AND entity_table = 'civicrm_group' AND group_type = 'Include'
            ORDER BY id
            LIMIT 1";
  $params = array(
    1 => array($mailingId, 'Integer'),
  );
  return (int) CRM_Core_DAO::singleValueQuery($query, $params);
}


/**
 * Find reminder group used for given campaign.
 * @param int $campaignId
 *
 * @return int
 */
function findExistingGroup($campaignId) {
  $query = "SELECT id
            FROM civicrm_group
            WHERE title LIKE %1
            ORDER BY id
            LIMIT 1";
  $params = array(
    1 => array('Reminder-__--CAMP-ID-' . $campaignId, 'String'),
  );
  return (int) CRM_Core_DAO::singleValueQuery($query, $params);
}


/**
 * Remove contacts from group.
 * @param int $groupId
 *
 * @throws \CiviCRM_API3_Exception
 */
function cleanGroup($groupId) {
  $query = "INSERT INTO civicrm_subscription_history (contact_id, group_id, date, method, status)
            SELECT contact_id, group_id, NOW(), 'Admin', 'Deleted'
            FROM civicrm_group_contact
            WHERE group_id = %1";
  $params = array(
    1 => array($groupId, 'Integer'),
  );
  CRM_Core_DAO::executeQuery($query, $params);
  $query = "DELETE FROM civicrm_group_contact
            WHERE group_id = %1";
  $params = array(
    1 => array($groupId, 'Integer'),
  );
  CRM_Core_DAO::executeQuery($query, $params);
}


/**
 * Add contacts to group.
 * @param array $contacts
 * @param int $groupId
 *
 * @throws \CiviCRM_API3_Exception
 */
function addContactsToGroup($contacts, $groupId) {
  foreach ($contacts as $contactId) {
    $params = array(
      'sequential' => 1,
      'group_id' => $groupId,
      'contact_id' => $contactId,
      'status' => "Added",
    );
    civicrm_api3('GroupContact', 'create', $params);
  }
}


/**
 * Add include group to mailing.
 * @param int $mailingId
 * @param int $groupId
 *
 * @throws \CiviCRM_API3_Exception
 */
function includeGroup($mailingId, $groupId) {
  $params = array(
    'mailing_id' => $mailingId,
    'group_type' => 'Include',
    'entity_table' => CRM_Contact_BAO_Group::getTableName(),
    'values' => array(array('entity_id' => $groupId)),
  );
  civicrm_api3('mailing_group', 'replace', $params);
}


/**
 * Add exclude group to mailing.
 * @param int $mailingId
 * @param int $groupId
 *
 * @throws \CiviCRM_API3_Exception
 */
function excludeGroup($mailingId, $groupId) {
  $params = array(
    'mailing_id' => $mailingId,
    'group_type' => 'Exclude',
    'entity_table' => CRM_Contact_BAO_Group::getTableName(),
    'values' => array(array('entity_id' => $groupId)),
  );
  civicrm_api3('mailing_group', 'replace', $params);
}


/**
 * Create new reminder group for campaign.
 *
 * @param int $campaignId
 * @param string $language
 *
 * @return int
 */
function createGroup($campaignId, $language) {
  $params = array(
    'sequential' => 1,
    'title' => 'Reminder-' . $language . '--CAMP-ID-' . $campaignId,
    'group_type' => CRM_Core_DAO::VALUE_SEPARATOR . '2' . CRM_Core_DAO::VALUE_SEPARATOR, // mailing type
    'visibility' => 'User and User Admin Only',
    'source' => 'speakcivi',
  );
  $result = civicrm_api3('Group', 'create', $params);
  return (int) $result['id'];
}


/**
 * Determine unique mailing name for given campaign. Format: YYYY-MM-DD-Reminder-ZZ--CAMP-ID-X
 * @param int $campaignId
 * @param string $language
 *
 * @return string
 */
function determineMailingName($campaignId, $language) {
  $dt = date('Y-m-d');
  $name = $dt . '-Reminder-' . $language . '--CAMP-ID-' . $campaignId;
  $query = "SELECT count(id) FROM civicrm_mailing WHERE name LIKE %1";
  $params = array(
    1 => array($name . '%', 'String'),
  );
  $count = (int) CRM_Core_DAO::singleValueQuery($query, $params);
  if ($count) {
    $name .= '_' . $count;
  }
  return $name;
}

/**
 * Submit reminder, trello #546
 *
 * @param $mailingId
 */
function submitReminder($mailingId) {
  $submitParams = [
    'id' => $mailingId,
    'approval_date' => 'now',
    'scheduled_date' => 'now',
    'scheduled_id' => 1,
  ];
  civicrm_api3('Mailing', 'submit', $submitParams);
}

function chooseFooter($language) {
  switch ($language) {
    case 'ES':
      return 13;

    case 'DE':
      return 10;

    case 'FR':
      return 12;

    case 'IT':
      return 11;

    case 'PL':
      return 15;

    case 'RO':
      return 31;

    default:
      return 14;
  }
}
