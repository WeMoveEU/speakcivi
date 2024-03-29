<?php

require_once 'CRM/Core/BAO/CustomField.php';

function _civicrm_api3_speakcivi_process_action_spec(&$params) {
  $params['message']['api.required'] = 1;
}

/**
 * API function for Rabbitizen to process action messages coming from Speakout
 */
function civicrm_api3_speakcivi_process_action($params) {
  $json_msg = json_decode($params['message']);
  if ($json_msg) {
    $msg_handler = new CRM_Speakcivi_Page_Speakcivi();
    try {
      $result = $msg_handler->runParam($json_msg);
      if ($result == 1) {
        return civicrm_api3_create_success();
      } elseif ($result == -1) {
        return civicrm_api3_create_error("runParams unsupported action type: " . $json_msg->action_type, ['retry_later' => FALSE]);
      } else {
        $session = CRM_Core_Session::singleton();
        $retry = _speakcivi_isConnectionLostError($session->getStatus());
        return civicrm_api3_create_error("runParam returned error code $result", ['retry_later' => $retry]);
      }
    } catch (CiviCRM_API3_Exception $ex) {
      $extraInfo = $ex->getExtraParams();
      $retry = strpos(CRM_Utils_Array::value('debug_information', $extraInfo), "try restarting transaction");
      return civicrm_api3_create_error(CRM_Core_Error::formatTextException($ex), ['retry_later' => $retry]);
    } catch (CRM_Speakcivi_Exception $ex) {
      return civicrm_api3_create_error(CRM_Core_Error::formatTextException($ex), ['retry_later' => FALSE]);
    } catch (Exception $ex) {
      return civicrm_api3_create_error(CRM_Core_Error::formatTextException($ex), ['retry_later' => FALSE]);
    }
  } else {
    return civicrm_api3_create_error("Could not decode {$params['message']}", ['retry_later' => FALSE]);
  }
}

function _speakcivi_isConnectionLostError($sessionStatus) {
  if (is_array($sessionStatus) && array_key_exists('title', $sessionStatus[0]) && $sessionStatus[0]['title'] == 'Mailing Error') {
    return !!strpos($sessionStatus[0]['text'], 'Connection lost to authentication server');
  }
  return FALSE;
}

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
  } else {
    $noMember = FALSE;
  }

  $campaignObj = new CRM_Speakcivi_Logic_Campaign($campaignId);
  $locale = $campaignObj->getLanguage();
  $params['from'] = $campaignObj->getSenderMail();
  $params['format'] = NULL;

  if ($confirmationBlock) {
    $params['subject'] = $campaignObj->getSubjectNew();
    $message = $campaignObj->getMessageNew();
  } else {
    $params['subject'] = $campaignObj->getSubjectCurrent();
    $message = $campaignObj->getMessageCurrent();
  }

  if (!$message) {
    if ($confirmationBlock) {
      $message = CRM_WeAct_Dictionary::getMessageNew($locale);
      $campaignObj->setCustomFieldBySQL($campaignId, $campaignObj->fieldMessageNew, $message);
    } else {
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
  } else {
    $baseConfirmUrl = 'civicrm/speakcivi/confirm';
    $baseOptoutUrl = 'civicrm/speakcivi/optout';
  }
  $url_confirm_and_keep = CRM_Utils_System::url(
    $baseConfirmUrl,
    "id=$contactId&aid=$activityId&cid=$campaignId&hash=$hash&utm_source=civicrm&utm_medium=email&utm_campaign=$utm_campaign&utm_content=$utm_content",
    TRUE
  );
  $url_confirm_and_not_receive = CRM_Utils_System::url(
    $baseOptoutUrl,
    "id=$contactId&aid=$activityId&cid=$campaignId&hash=$hash&utm_source=civicrm&utm_medium=email&utm_campaign=$utm_campaign&utm_content=$utm_content",
    TRUE
  );

  $template = CRM_Core_Smarty::singleton();
  $template->assign('url_confirm_and_keep', $url_confirm_and_keep);
  $template->assign('url_confirm_and_not_receive', $url_confirm_and_not_receive);

  /* SHARING_BLOCK */
  $template->assign('url_campaign', $campaignObj->getUrlCampaign());
  $template->assign('url_campaign_encoded', urlencode(extendUrl($campaignObj->getUrlCampaign())));
  $template->assign('url_campaign_fb', prepareCleanUrl(extendUrl($campaignObj->getUrlCampaign())));
  $template->assign('share_url', extendUrl($campaignObj->getUrlCampaign()) . 'action=share&submit&');
  $template->assign('language_form_url', $campaignObj->getLanguageFormUrl());
  $template->assign('utm_campaign', $utm_campaign);
  $template->assign('share_utm_campaign', sha1(CIVICRM_SITE_KEY . $activityId));
  $template->assign('share_utm_source', urlencode($params['share_utm_source']));
  $template->assign('share_email', CRM_WeAct_Dictionary::getShareEmail($locale));
  $template->assign('share_facebook', CRM_WeAct_Dictionary::getShareFacebook($locale));
  $template->assign('share_twitter', CRM_WeAct_Dictionary::getShareTwitter($locale));
  $template->assign('share_whatsapp', CRM_WeAct_Dictionary::getShareWhatsapp($locale));
  $template->assign('language_button', CRM_WeAct_Dictionary::getLanguageButton($locale));
  $template->assign('twitter_share_text', urlencode($campaignObj->getTwitterShareText()));
  $template->assign('contact', $contact);
  $template->assign('campaign_name', $campaignObj->campaign['description']);
  $share_whatsapp_web = $template->fetch('string:' . CRM_WeAct_Dictionary::getShareWhatsappWeb($locale));
  $template->assign('share_whatsapp_web', $share_whatsapp_web);

  /* FETCHING SMARTY TEMPLATES */
  $params['subject'] = $template->fetch('string:' . $params['subject']);
  $locales = getLocale($locale);
  $confirmationBlockHtml = $template->fetch('../templates/CRM/Speakcivi/Page/ConfirmationBlock.' . $locales['html'] . '.html.tpl');
  $confirmationBlockText = $template->fetch('../templates/CRM/Speakcivi/Page/ConfirmationBlock.' . $locales['text'] . '.text.tpl');
  $privacyBlock = $template->fetch('../templates/CRM/Speakcivi/Page/PrivacyBlock.' . $locales['html'] . '.tpl');
  $sharingBlockHtml = $template->fetch('../templates/CRM/Speakcivi/Page/SharingBlock.html.tpl');
  $languageBlockHtml = $template->fetch('../templates/CRM/Speakcivi/Page/LanguageBlock.html.tpl');
  $message = $template->fetch('string:' . $message);

  $messageHtml = str_replace("#CONFIRMATION_BLOCK", $confirmationBlockHtml, $message);
  $messageText = str_replace("#CONFIRMATION_BLOCK", $confirmationBlockText, $message);
  $messageHtml = str_replace("#PRIVACY_BLOCK", $privacyBlock, $messageHtml);
  $messageText = str_replace("#PRIVACY_BLOCK", $privacyBlock, $messageText);
  $messageHtml = str_replace("#SHARING_BLOCK", $sharingBlockHtml, $messageHtml);
  $messageText = str_replace("#SHARING_BLOCK", $sharingBlockHtml, $messageText);
  $messageHtml = str_replace("#LANGUAGE_BLOCK", $languageBlockHtml, $messageHtml);
  $messageText = str_replace("#LANGUAGE_BLOCK", $languageBlockHtml, $messageText);

  $params['html'] = html_entity_decode($messageHtml);
  $params['text'] = html_entity_decode(convertHtmlToText($messageText));
  if ($campaignObj->isYoumove()) {
    $params['groupName'] = 'SpeakCivi YouMove';
  } else {
    $params['groupName'] = 'SpeakCivi WeMove';
  }
  $params['custom-activity-id'] = $activityId;
  $params['custom-campaign-id'] = $campaignId;
  try {
    $sent = CRM_Utils_Mail::send($params);
    return civicrm_api3_create_success($sent, $params);
  } catch (CiviCRM_API3_Exception $exception) {
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
  $groupId = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'group_id');
  $limit = $params['limit'];
  CRM_Speakcivi_Cleanup_Leave::truncateTemporary();
  CRM_Speakcivi_Cleanup_Leave::loadTemporary($groupId, $limit);
  $data = CRM_Speakcivi_Cleanup_Leave::getDataForActivities();
  CRM_Speakcivi_Cleanup_Leave::createActivitiesInBatch($data);
  $count = CRM_Speakcivi_Cleanup_Leave::countTemporaryContacts();
  CRM_Speakcivi_Cleanup_Leave::truncateTemporary();
  $results = array(
    'count' => $count,
    'time' => microtime(TRUE) - $start,
  );
  return civicrm_api3_create_success($results, $params);
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
  } else {
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
 * (as a result of language change activity) and move them to the proper group.
 * This assumes that language groups have their 'source' column set with the
 * matching locale (with special case uk EN...).
 * @param languages: comma-separated list of target languages to limit the search
 * @param limit: Maximum number of contacts to update
 */
function civicrm_api3_speakcivi_fixlanguagegroup($params) {
  $activityTypeId = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Self-care');
  $languageGroupNameSuffix = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'language_group_name_suffix');
  $p = new CRM_Speakcivi_Page_Post();
  $enGroups = array(
    'GB' => $p->findLanguageGroupId('en', 'GB', $languageGroupNameSuffix),
    '*'  => $p->findLanguageGroupId('en', 'ZZ', $languageGroupNameSuffix),
  );

  $lang_filter = '1=1';
  if ($params['languages']) {
    $languages = explode(',', $params['languages']);
    $lang_filter = "c.preferred_language IN ('" . implode("', '", $languages) . "')";
  }

  $limit = '';
  if ($params['limit']) {
    $limit = 'LIMIT ' . $params['limit'];
  }

  $query = "SELECT
      c.id AS contact_id,
      c.preferred_language AS preferred_language,
      ctry.iso_code AS country,
      gl.id AS current_group_id,
      MIN(expected.id) AS expected_group_id
    FROM civicrm_activity a
    JOIN civicrm_activity_contact ac ON ac.activity_id = a.id AND ac.record_type_id = 2
    JOIN civicrm_contact c ON c.id = ac.contact_id
    JOIN civicrm_address addr ON addr.contact_id = c.id AND addr.is_primary
    JOIN civicrm_country ctry ON ctry.id = addr.country_id
    JOIN civicrm_group gl ON gl.source LIKE '__\___'
                          AND (gl.source != c.preferred_language
                               OR (gl.id = {$enGroups['GB']} AND ctry.iso_code != 'GB')
                               OR (gl.id = {$enGroups['*']}  AND ctry.iso_code  = 'GB'))
    JOIN civicrm_group_contact gcl ON gcl.contact_id = c.id AND gcl.status='Added' AND gcl.group_id = gl.id
    JOIN civicrm_group expected ON expected.source = c.preferred_language
    WHERE a.activity_type_id = $activityTypeId AND a.subject = 'Language change' AND $lang_filter
    GROUP BY contact_id, preferred_language, country, current_group_id
    $limit
  ";

  $count = 0;
  $dao = CRM_Core_DAO::executeQuery($query);
  while ($dao->fetch()) {
    $contactIds = array($dao->contact_id);
    CRM_Contact_BAO_GroupContact::removeContactsFromGroup(
      $contactIds,
      $dao->current_group_id,
      'Spkcivi',
      'Removed',
      'fixlanguagegroup'
    );

    if ($dao->preferred_language == 'en_GB') {
      $expected = CRM_Utils_Array::value($dao->country, $enGroups, $enGroups['*']);
    } else {
      $expected = $dao->expected_group_id;
    }
    CRM_Contact_BAO_GroupContact::addContactsToGroup(
      $contactIds,
      $expected,
      'Spkcivi',
      'Added',
      'fixlanguagegroup'
    );
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

/**
 * @param $params
 *
 * @return array
 * @throws \CiviCRM_API3_Exception
 */
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
    } else {
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
    $urls = ['url_confirm_and_keep' => $url_confirm_and_keep, 'url_confirm_and_not_receive' => $url_confirm_and_not_receive];
    $messageHtml[$cid] = prepareConfirmMessage($msg, $confirmationBlockHtml, $urls);
    $messageText[$cid] = convertHtmlToText(prepareConfirmMessage($msg, $confirmationBlockText, $urls));
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
        } else {
          $includeGroupId = createGroup($cid, $language[$cid]);
          addContactsToGroup($contacts[$cid], $includeGroupId);
          includeGroup($mailingId, $includeGroupId);
        }
      } else {
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
          'footer_id' => CRM_Speakcivi_Logic_Language::chooseFooter($language[$cid]),
        );
        $mailing = new CRM_Mailing_BAO_Mailing();
        $mm = $mailing->add($params);
        setMetadata($mm->id);

        excludeGroup($mm->id, $groupId);

        if ($existingGroupId = findExistingGroup($cid)) {
          cleanGroup($existingGroupId);
          addContactsToGroup($contacts[$cid], $existingGroupId);
          includeGroup($mm->id, $existingGroupId);
        } else {
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

function prepareConfirmMessage($msg, $block, $vars) {
  $msg = str_replace("#CONFIRMATION_BLOCK", $block, $msg);
  //FIXME This could be done more efficiently
  foreach ($vars as $var => $value) {
    $msg = str_replace('{$' . $var . '}', $value, $msg);
  }
  return removeSmartyIfClause(convertTokens(removeDelim($msg)));
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
  } else {
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

/**
 * FIXME delete this function once Speakout does not use it anymore
 * @deprecated
 */
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
  $data = null;
  $campaigns = civicrm_api3('Campaign', 'get', ['name' => $params['campaign_name'], 'return' => 'id']);
  if ($campaigns['is_error']) {
    return civicrm_api3_create_error("Error while retrieving {$params['campaign_name']}", $data);
  } else {
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
 * Set default values for metadata fields.
 *
 * @param int $mailingId
 *
 * @throws \CiviCRM_API3_Exception
 */
function setMetadata($mailingId) {
  $mailingType = CRM_Core_BAO_CustomField::getCustomFieldID('mailing_type', 'mailingdata', TRUE);
  $askType = CRM_Core_BAO_CustomField::getCustomFieldID('mailing_ask_type', 'mailingdata', TRUE);
  $personalisedSubject = CRM_Core_BAO_CustomField::getCustomFieldID('personalised_subject', 'mailingdata', TRUE);
  $hasPicture = CRM_Core_BAO_CustomField::getCustomFieldID('has_pictures', 'mailingdata', TRUE);
  $params = [
    'sequential' => 1,
    'id' => $mailingId,
  ];
  if ($mailingType) {
    $params[$mailingType] = 'kicker';
  }
  if ($askType) {
    $params[$askType] = 'consent';
  }
  if ($personalisedSubject) {
    $params[$personalisedSubject] = 0;
  }
  if ($hasPicture) {
    $params[$hasPicture] = 0;
  }
  if (count($params) > 2) {
    civicrm_api3('Mailing', 'create', $params);
  }
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


/**
 * Create welcome groups and email Olga Iskra with the links.
 *
 */
function civicrm_api3_speakcivi_welcome_series_groups($params) {

  $min_members = 100;
  if (array_key_exists('threshold', $params)) {
    $min_members = (int) $params['threshold'];
  }
  $from_address = 'info@not-a2.eu';
  if (array_key_exists('from', $params)) {
    $from_address = $params['from'];
  }
  $to = array('tech@wemove.eu');
  if (array_key_exists('to', $params)) {
    $to = preg_split('/[,; ]+/', $params['to']);
  }
  CRM_Core_Error::debug_log_message("[speakcivi.welcome_series_groups] Creating groups for Welcome Series with a threshold of {$min_members}.");

  $sqlParams = array(
    1 => array($min_members, 'Integer')
  );

  # 1. find which groups need to be created
  $dao = CRM_Core_DAO::executeQuery(
    <<<SQL
    SELECT group_id,
        concat(
            'Welcome Emails - ',
            title,
            ' (',
            DATE_FORMAT(NOW() - INTERVAL 7 DAY, '%Y-%m-%d'),
            ' - ',
            DATE_FORMAT(NOW(), '%Y-%m-%d'),
            ')'
        ) group_name,
        COUNT(*) members
    from civicrm_contact cc
        join civicrm_group_contact cgc ON (cc.id = cgc.contact_id)
        join civicrm_group cg ON (
            cg.id = cgc.group_id
            AND cg.name LIKE '%activists%'
        )
    WHERE cc.source LIKE 'speakout petition 1____'
        AND cc.created_date BETWEEN NOW() - INTERVAL 7 DAY
        AND NOW()
    GROUP BY group_id
    HAVING COUNT(*) > %1
SQL,
    $sqlParams
  );

  $groups_to_create = [];
  while($dao->fetch()) {
    $group_name = $dao->group_name;
    $language_group_id = $dao->group_id;
    $groups_to_create[] = [$group_name, $language_group_id];
  }
  # 2. for each group we need to create, create the group and insert the new members
  foreach ($groups_to_create as $group) {
    $group_name = $group[0];
    $language_group_id = $group[1];
    $welcome_series_group_id = -1;

    CRM_Core_Error::debug_log_message("[speakcivi.welcome_series_groups] Building group $group_name from $language_group_id");

    $existing = civicrm_api3('Group', 'get', array("title" => $group_name));
    if ($existing['count'] == 1) {
      CRM_Core_Error::debug_log_message(json_encode($existing));
      $welcome_series_group_id = (int) $existing['id'];
    } else {
      $params = array(
        'sequential' => 1,
        'title' => $group_name,
        'group_type' => CRM_Core_DAO::VALUE_SEPARATOR . '2' . CRM_Core_DAO::VALUE_SEPARATOR,
        'visibility' => 'User and User Admin Only',
        'source' => 'speakcivi',
      );
      $result = civicrm_api3('Group', 'create', $params);
      $welcome_series_group_id = (int) $result['id'];
    }

    if ($welcome_series_group_id == -1) {
      throw new Exception("[speakcivi.welcome_series_groups] Couldn't find or create a group, I just can't go on.");
    }

    $groups[$welcome_series_group_id] = $group_name;

    # 3.find the members in $group_id
    $sqlParams = array(
      1 => array($language_group_id, 'Integer')
    );

    CRM_Core_Error::debug_log_message("[speakcivi.welcome_series_groups] Finding members for group $language_group_id $group_name");

    $dao = CRM_Core_DAO::executeQuery(
      <<<SQL
            SELECT DISTINCT cc.id
            from civicrm_contact cc
            join civicrm_group_contact cgc ON (cc.id = cgc.contact_id)
            join civicrm_group cg ON (
                cg.id = cgc.group_id
                AND cg.name LIKE '%activists%'
            )
            WHERE cc.source LIKE 'speakout petition 1____'
              AND cc.created_date BETWEEN NOW() - INTERVAL 7 DAY
              AND NOW()
              AND cg.id = %1
  SQL,
      $sqlParams
    );

    # stuff all the member ids into an array, hope there aren't too many!!
    $values = array();
    while ($dao->fetch()) {
      $values[] = "(" . $dao->id  . ",$welcome_series_group_id,'Added')";
    }

    # insert the new members into the new group
    $values = implode(",", $values);
    $insert = <<<SQL
            INSERT IGNORE INTO civicrm_group_contact
            (contact_id, group_id, status)
            VALUES
            $values
SQL;
    CRM_Core_DAO::executeQuery($insert);
    CRM_Core_Error::debug_log_message("and i'm gone... ");
  }

  # CRM_Core_Error::debug_log_message("Letting someone know about the new groups;");

  $message = <<<HTML
<p>Hello!</p>
<p></p>
<p>Here are the Welcome Series groups for you :</p>
<p></p>
<ul>
HTML;
  foreach ($groups as $id => $name) {
    $message .= "<li><a href='https://www.wemove.eu/civicrm/group/search?force=1&context=smog&gid={$id}'>{$name}</a>\n";
  }
  if (count($groups) == 0) {
    $message .= "<li>No welcome series groups for this week!</li>";
  }
  $message .= <<<HTML
</ul>
<p></p>
<p>-- Your friendly neighborhood Tech team!</p>
<pre>
  {json_encode($groups)}
</pre>
<p></p>
HTML;

  foreach ($to as $to_addr) {
    $email = array(
      'from' => $from_address,
      'cc' => 'tech@wemove.eu',
      'toName' => 'Welcome Series Organiser',
      'toEmail' => $to_addr,
      'subject' => 'Your Weekly Welcome Series Groups!',
      'html' => $message
    );
    CRM_Utils_Mail::send($email);
  }

  return civicrm_api3_create_success($groups);
}


function civicrm_api3_speakcivi_trialing_pool_group($params) {
  /*
    Maintain a group of :

    - from INT_EN members group
    - DROPPED: from EU countries plus Switzerland
    - who didn't receive any mailing in last 4 days (check if it's not too limiting?)
    - who didn't receive any trial within last 7 days

   */

  $group_details =  ['title' => "Trial Pool INT-EN (excludes recent mailings)", 'name' => "trialing-pool-int-en"];

  CRM_Core_Transaction::create(TRUE)->run(function (CRM_Core_Transaction $tx) use (&$group_details) {
    $result = civicrm_api3('Group', 'get', $group_details);
    if ($result['count'] == 1) {
      $group_id = $result['id'];
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_group_contact WHERE group_id = $group_id");
    } else {
      $result = civicrm_api3('Group', 'create', $group_details);
      $group_id = $result['id'];
    }

    CRM_Core_DAO::executeQuery(
      "CREATE TEMPORARY TABLE trial_language (contact_id integer PRIMARY KEY)"
    );
    CRM_Core_DAO::executeQuery(
      "CREATE TEMPORARY TABLE trial_recent_mailing (contact_id integer PRIMARY KEY)"
    );

    CRM_Mailing_BAO_Mailing::select_into_load_data(
      "_trial_group",
      "SELECT DISTINCT g.contact_id
        FROM civicrm_group_contact g
        WHERE group_id = (
            SELECT id
            FROM civicrm_group
            WHERE title = 'English language Members INT'
        )
        ",
      "trial_language",
      ["contact_id"]
    );

    CRM_Mailing_BAO_Mailing::select_into_load_data(
      "_trial_group",
      "SELECT e.contact_id
        FROM civicrm_mailing_event_queue e
        JOIN civicrm_mailing_job j ON (e.job_id = j.id)
        WHERE j.mailing_id in (
            SELECT id
            FROM civicrm_mailing
            WHERE (
                    (
                        scheduled_date > NOW() - interval 7 day
                        AND name LIKE '%-trial-%'
                    )
                    OR (scheduled_date > NOW() - interval 4 day)
                )
                AND is_completed = 1
        )",
      "trial_recent_mailing",
      ["contact_id"]
    );

    CRM_Mailing_BAO_Mailing::select_into_load_data(
      "_trial_group",
      "SELECT DISTINCT 'Added' status, l.contact_id contact_id, $group_id group_id
        FROM trial_language l
        LEFT JOIN trial_recent_mailing m ON (l.contact_id=m.contact_id)
        WHERE m.contact_id IS NULL
        ",
      'civicrm_group_contact',
      ["status", "contact_id", "group_id"]
    );

    $group_details["count"] = CRM_Core_DAO::singleValueQuery(
      "SELECT COUNT(*)
        FROM civicrm_group_contact
        WHERE group_id = $group_id
        "
    );
  });

  return civicrm_api3_create_success($group_details);
}
