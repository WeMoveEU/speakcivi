<?php

function civicrm_api3_speakcivi_update_stats ($params) {
  $config = CRM_Core_Config::singleton();
  $sql = file_get_contents(dirname( __FILE__ ) .'/../../sql/update.sql', true);
  CRM_Utils_File::sourceSQLFile($config->dsn, $sql, NULL, true);
  return civicrm_api3_create_success(array("query"=>$sql), $params);
}

function _civicrm_api3_speakcivi_sendconfirm_spec(&$params) {
  $params['toEmail']['api.required'] = 1;
  $params['contact_id']['api.required'] = 1;
  $params['campaign_id']['api.required'] = 1;
  $params['from']['api.default'] = html_entity_decode(CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'from'));
  $params['share_utm_source']['api.default'] = 'member';
}


function civicrm_api3_speakcivi_sendconfirm($params) {

  $confirmationBlock = $params['confirmation_block'];
  $contactId = $params['contact_id'];
  $campaignId = $params['campaign_id'];
  $activityId = $params['activity_id'];

  $campaignObj = new CRM_Speakcivi_Logic_Campaign($campaignId);
  $locale = $campaignObj->getLanguage();
  $params['from'] = $campaignObj->getSenderMail();
  $params['format'] = null;

  if ($confirmationBlock) {
    $params['subject'] = $campaignObj->getSubjectNew();
    $message = $campaignObj->getMessageNew();
  } else {
    $params['subject'] = $campaignObj->getSubjectCurrent();
    $message = $campaignObj->getMessageCurrent();
  }

  if (!$message) {
    if ($confirmationBlock) {
      $message = CRM_Speakcivi_Tools_Dictionary::getMessageNew($locale);
      $campaignObj->setCustomFieldBySQL($campaignId, $campaignObj->fieldMessageNew, $message);
    } else {
      $message = CRM_Speakcivi_Tools_Dictionary::getMessageCurrent($locale);
      $campaignObj->setCustomFieldBySQL($campaignId, $campaignObj->fieldMessageCurrent, $message);
    }
  }

  $contact = array();
  $params_contact = array(
    'id' => $contactId,
    'sequential' => 1,
  );
  $result = civicrm_api3('Contact', 'get', $params_contact);
  if ($result['count'] == 1) {
    $contact = $result['values'][0];
  }

  /* CONFIRMATION_BLOCK */
  $hash = sha1(CIVICRM_SITE_KEY.$contactId);
  $utm_content = 'version_'.($contactId % 2);
  $utm_campaign = $campaignObj->getUtmCampaign();
  $url_confirm_and_keep = CRM_Utils_System::url('civicrm/speakcivi/confirm',
    "id=$contactId&aid=$activityId&cid=$campaignId&hash=$hash&utm_source=civicrm&utm_medium=email&utm_campaign=$utm_campaign&utm_content=$utm_content", true);
  $url_confirm_and_not_receive = CRM_Utils_System::url('civicrm/speakcivi/optout',
    "id=$contactId&aid=$activityId&cid=$campaignId&hash=$hash&utm_source=civicrm&utm_medium=email&utm_campaign=$utm_campaign&utm_content=$utm_content", true);

  $template = CRM_Core_Smarty::singleton();
  $template->assign('url_confirm_and_keep', $url_confirm_and_keep);
  $template->assign('url_confirm_and_not_receive', $url_confirm_and_not_receive);

  /* SHARING_BLOCK */
  $template->assign('url_campaign', $campaignObj->getUrlCampaign());
  $template->assign('url_campaign_fb', prepareCleanUrl($campaignObj->getUrlCampaign()));
  $template->assign('utm_campaign', $campaignObj->getUtmCampaign());
  $template->assign('share_utm_source', urlencode($params['share_utm_source']));
  $template->assign('share_facebook', CRM_Speakcivi_Tools_Dictionary::getShareFacebook($locale));
  $template->assign('share_twitter', CRM_Speakcivi_Tools_Dictionary::getShareTwitter($locale));
  $template->assign('twitter_share_text', urlencode($campaignObj->getTwitterShareText()));
  $template->assign('contact', $contact);

  /* FETCHING SMARTY TEMPLATES */
  $params['subject'] = $template->fetch('string:'.$params['subject']);
  $locales = getLocale($locale);
  $confirmationBlockHtml = $template->fetch('../templates/CRM/Speakcivi/Page/ConfirmationBlock.'.$locales['html'].'.html.tpl');
  $confirmationBlockText = $template->fetch('../templates/CRM/Speakcivi/Page/ConfirmationBlock.'.$locales['text'].'.text.tpl');
  $sharingBlockHtml = $template->fetch('../templates/CRM/Speakcivi/Page/SharingBlock.html.tpl');
  $message = $template->fetch('string:'.$message);

  $messageHtml = str_replace("#CONFIRMATION_BLOCK", $confirmationBlockHtml, $message);
  $messageText = str_replace("#CONFIRMATION_BLOCK", $confirmationBlockText, $message);
  $messageHtml = str_replace("#SHARING_BLOCK", $sharingBlockHtml, $messageHtml);
  $messageText = str_replace("#SHARING_BLOCK", $sharingBlockHtml, $messageText);

  $params['html'] = html_entity_decode($messageHtml);
  $params['text'] = html_entity_decode(convertHtmlToText($messageText));
  $params['groupName'] = 'SpeakCivi Email Sender';
  $sent = CRM_Utils_Mail::send($params);
  return civicrm_api3_create_success($sent, $params);
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
  $start = microtime(true);
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
    $ids = array();
    foreach ($data as $k => $v) {
      $ids[$v['id']] = $v['id'];
    }
    $results = array(
      'count' => $count,
      'time' => microtime(true) - $start,
      'ids' => $ids,
    );
    return civicrm_api3_create_success($results, $params);
  } catch (Exception $ex) {
    $tx->rollback()->commit();
    throw $ex;
  }
}


function _civicrm_api3_speakcivi_join_spec(&$params) {
  $params['limit']['api.required'] = 1;
  $params['limit']['api.default'] = 1000;
}


function civicrm_api3_speakcivi_join($params) {
  $start = microtime(true);
  $groupId = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'group_id');
  $activityTypeId  = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'activity_type_join');
  $limit = $params['limit'];
  $query = "SELECT speakciviUpdateJoinActivities(%1, %2, %3) AS results;";
  $query_params = array(
    1 => array($groupId, 'Integer'),
    2 => array($activityTypeId, 'Integer'),
    3 => array($limit, 'Integer'),
  );
  $count = (int)CRM_Core_DAO::singleValueQuery($query, $query_params);
  $results = array(
    'count' => $count,
    'time' => microtime(true) - $start,
  );
  return civicrm_api3_create_success($results, $params);
}


function _civicrm_api3_speakcivi_removelanguagegroup_spec(&$params) {
  $params['limit']['api.required'] = 1;
  $params['limit']['api.default'] = 10000;
}


function civicrm_api3_speakcivi_removelanguagegroup($params) {
  $start = microtime(true);
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
    $count = (int)CRM_Core_DAO::singleValueQuery($query, $query_params);
    $results = array(
      'count' => $count,
      'time' => microtime(true) - $start,
    );
    return civicrm_api3_create_success($results, $params);
  } else {
    $data = array(
      'groupId' => $groupId,
      'languageGroupNameSuffix' => $languageGroupNameSuffix,
      'limit' => $limit
    );
    return civicrm_api3_create_error('Not valid params', $data);
  }
}


function _civicrm_api3_speakcivi_remind_spec(&$params) {
  $params['days']['api.required'] = 1;
  $params['days']['api.default'] = 3;
  $params['days_contact']['api.required'] = 1;
  $params['days_contact']['api.default'] = 3;
}


function civicrm_api3_speakcivi_remind($params) {
  // how old not confirmed petitions
  $start = microtime(true);
  $days = $params['days'];
  $daysContact = $params['days_contact'];
  if ($daysContact < $days) {
    $daysContact = $days;
  }
  $groupId = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'group_id');
  $activityTypeId = CRM_Core_OptionGroup::getValue('activity_type', 'Petition', 'name', 'String', 'value');
  $adminId = 1;

  $query = "SELECT acp.activity_id, ap.campaign_id, acp.contact_id
            FROM civicrm_activity ap
              JOIN civicrm_activity_contact acp ON acp.activity_id = ap.id
              JOIN civicrm_contact c ON c.id = acp.contact_id
              LEFT JOIN civicrm_group_contact gc ON gc.contact_id = acp.contact_id AND gc.group_id = %1 AND gc.status = 'Added'
            WHERE ap.activity_type_id = %2 AND ap.status_id = 1 AND ap.activity_date_time <= date_add(current_date, INTERVAL -%3 DAY)
                AND c.created_date >= date_add(current_date, INTERVAL -%4 DAY)
                AND c.is_opt_out = 0 AND c.is_deleted = 0 AND c.is_deceased = 0 AND c.do_not_email = 0 AND gc.id IS NULL";
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
  $locale = array();
  $email = array();
  foreach ($campaigns as $cid) {
    $campaignObj = new CRM_Speakcivi_Logic_Campaign($cid);
    $message[$cid] = $campaignObj->getMessageNew();
    $subject[$cid] = substr(removeSmartyIfClause(convertTokens($campaignObj->getSubjectNew())), 0, 128);
    $utmCampaign[$cid] = $campaignObj->getUtmCampaign();
    $locale[$cid] = $campaignObj->getLanguage();
    $email[$cid] = parseSenderEmail($campaignObj->getSenderMail());
  }

  // fetch confirmation block
  $messageHtml = array();
  $messageText = array();
  foreach ($message as $cid => $msg) {
    $url_confirm_and_keep = CRM_Utils_System::url('civicrm/speakcivi/confirm', null, true).
      "?id={contact.contact_id}&cid=$cid&hash={speakcivi.confirmation_hash}&utm_source=civicrm&utm_medium=email&utm_campaign=".$utmCampaign[$cid];
    $url_confirm_and_not_receive = CRM_Utils_System::url('civicrm/speakcivi/optout', null, true).
      "?id={contact.contact_id}&cid=$cid&hash={speakcivi.confirmation_hash}&utm_source=civicrm&utm_medium=email&utm_campaign=".$utmCampaign[$cid];
    $locales = getLocale($locale[$cid]);
    $confirmationBlockHtml = implode('', file(dirname(__FILE__).'/../../templates/CRM/Speakcivi/Page/ConfirmationBlock.'.$locales['html'].'.html.tpl'));
    $confirmationBlockText = implode('', file(dirname(__FILE__).'/../../templates/CRM/Speakcivi/Page/ConfirmationBlock.'.$locales['text'].'.text.tpl'));
    $confirmationBlockHtml = str_replace('{$url_confirm_and_keep}', $url_confirm_and_keep, $confirmationBlockHtml);
    $confirmationBlockHtml = str_replace('{$url_confirm_and_not_receive}', $url_confirm_and_not_receive, $confirmationBlockHtml);
    $confirmationBlockText = str_replace('{$url_confirm_and_keep}', $url_confirm_and_keep, $confirmationBlockText);
    $confirmationBlockText = str_replace('{$url_confirm_and_not_receive}', $url_confirm_and_not_receive, $confirmationBlockText);
    $messageHtml[$cid] = removeSmartyIfClause(convertTokens(removeDelim(str_replace("#CONFIRMATION_BLOCK", $confirmationBlockHtml, $msg))));
    $messageText[$cid] = convertHtmlToText(removeSmartyIfClause(convertTokens(removeDelim(str_replace("#CONFIRMATION_BLOCK", $confirmationBlockText, $msg)))));
  }

  foreach ($campaigns as $cid) {
    $sentContacts = findSentContacts($cid);
    $contacts[$cid] = excludeContacts($contacts[$cid], $sentContacts);
    if (is_array($contacts[$cid]) && count($contacts[$cid]) > 0) {
      if ($mailingId = findNotCompletedMailing($cid)) {
        if ($linkedGroupId = findLinkedGroup($mailingId)) {
          addContactsToGroup($contacts[$cid], $linkedGroupId);
        } else {
          $includeGroupId = createGroup($cid);
          addContactsToGroup($contacts[$cid], $includeGroupId);
          includeGroup($mailingId, $includeGroupId);
        }
      } else {
        $name = determineMailingName($cid);
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
        );
        $mailing = new CRM_Mailing_BAO_Mailing();
        $mm = $mailing->add($params);

        excludeGroup($mm->id, $groupId);

        if ($existingGroupId = findExistingGroup($cid)) {
          cleanGroup($existingGroupId);
          addContactsToGroup($contacts[$cid], $existingGroupId);
          includeGroup($mm->id, $existingGroupId);
        } else {
          $includeGroupId = createGroup($cid);
          addContactsToGroup($contacts[$cid], $includeGroupId);
          includeGroup($mm->id, $includeGroupId);
        }
      }
    }
  }
  $results = array(
    'time' => microtime(true) - $start,
  );
  return civicrm_api3_create_success($results, $params);
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
    if (file_exists(dirname(__FILE__).'/../../templates/CRM/Speakcivi/Page/ConfirmationBlock.'.$locale.'.'.$type.'.tpl')) {
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
      $html = str_replace($tag, $matches[2][$id]."\n".str_replace(' ', '+', $matches[1][$id]), $html);
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
 * Remove delim code from string (confirmation block).
 * @param string $str
 *
 * @return mixed
 */
function removeDelim($str) {
  $first = strpos($str, '{ldelim}');
  $last = strrpos($str, '{rdelim}');
  if ($first !== false && $last !== false) {
    $str = substr_replace($str, '', $first, $last-$first+8);
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
            WHERE campaign_id = %1 AND name LIKE '%Reminder--CAMP-ID-%' AND is_completed IS NULL
            ORDER BY id
            LIMIT 1";
  $params = array(
    1 => array($campaignId, 'Integer'),
  );
  return (int)CRM_Core_DAO::singleValueQuery($query, $params);
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
            WHERE m.campaign_id = %1 AND m.name LIKE '%Reminder--CAMP-ID-%'";
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
  return (int)CRM_Core_DAO::singleValueQuery($query, $params);
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
            WHERE title = %1";
  $params = array(
    1 => array('Reminder--CAMP-ID-'.$campaignId, 'String'),
  );
  return (int)CRM_Core_DAO::singleValueQuery($query, $params);
}


/**
 * Remove contacts from group.
 * @param int $groupId
 *
 * @throws \CiviCRM_API3_Exception
 */
function cleanGroup($groupId) {
  $params = array(
    'sequential' => 1,
    'return' => "contact_id",
    'group_id' => $groupId,
    'status' => "Added",
  );
  $result = civicrm_api3('GroupContact', 'get', $params);
  if ($result['count'] > 0) {
    foreach ($result['values'] as $c) {
      $params = array(
        'sequential' => 1,
        'group_id' => $groupId,
        'contact_id' => $c['contact_id'],
        'status' => "Removed",
      );
      civicrm_api3('GroupContact', 'create', $params);
    }
  }
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
 * @param int $campaignId
 *
 * @return int
 * @throws \CiviCRM_API3_Exception
 */
function createGroup($campaignId) {
  $params = array(
    'sequential' => 1,
    'title' => 'Reminder--CAMP-ID-'.$campaignId,
    'group_type' => CRM_Core_DAO::VALUE_SEPARATOR . '2' . CRM_Core_DAO::VALUE_SEPARATOR, // mailing type
    'visibility' => 'User and User Admin Only',
    'source' => 'speakcivi',
  );
  $result = civicrm_api3('Group', 'create', $params);
  return (int)$result['id'];
}


/**
 * Determine unique mailing name for given campaign. Format: YYYY-MM-DD-Reminder--CAMP-ID-X
 * @param int $campaignId
 *
 * @return string
 */
function determineMailingName($campaignId) {
  $dt = date('Y-m-d');
  $name = $dt.'-Reminder--CAMP-ID-'.$campaignId;
  $query = "SELECT count(id) FROM civicrm_mailing WHERE name LIKE %1";
  $params = array(
    1 => array($name.'%', 'String'),
  );
  $count = (int)CRM_Core_DAO::singleValueQuery($query, $params);
  if ($count) {
    $name .= '_'.$count;
  }
  return $name;
}
