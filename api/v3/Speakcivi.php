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
}


function civicrm_api3_speakcivi_remind($params) {

  // how old not confirmed petitions
  $days = 3;
  $groupId = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'group_id');
  $activityTypeId = 32; // Signature Petition

  $query = "SELECT acp.activity_id, ap.campaign_id, acp.contact_id
            FROM civicrm_activity ap
              JOIN civicrm_activity_contact acp ON acp.activity_id = ap.id
              JOIN civicrm_contact c ON c.id = acp.contact_id
              LEFT JOIN civicrm_group_contact gc ON gc.contact_id = acp.contact_id AND gc.group_id = %1 AND gc.status = 'Added'
            WHERE ap.activity_type_id = %2 AND ap.status_id = 1 AND ap.activity_date_time <= date_add(current_date, INTERVAL -%3 DAY)
                AND c.is_opt_out = 0 AND c.is_deleted = 0 AND c.is_deceased = 0 AND c.do_not_email = 0 AND gc.id IS NULL";
  $params = array(
    1 => array($groupId, 'Integer'),
    2 => array($activityTypeId, 'Integer'),
    3 => array($days, 'Integer'),
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
    $subject[$cid] = $campaignObj->getSubjectNew();
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
    $confirmationBlockHtml = implode('', file('../templates/CRM/Speakcivi/Page/ConfirmationBlock.'.$locales['html'].'.html.tpl'));
    $confirmationBlockText = implode('', file('../templates/CRM/Speakcivi/Page/ConfirmationBlock.'.$locales['text'].'.text.tpl'));
    $confirmationBlockHtml = str_replace('{$url_confirm_and_keep}', $url_confirm_and_keep, $confirmationBlockHtml);
    $confirmationBlockHtml = str_replace('{$url_confirm_and_not_receive}', $url_confirm_and_not_receive, $confirmationBlockHtml);
    $confirmationBlockText = str_replace('{$url_confirm_and_keep}', $url_confirm_and_keep, $confirmationBlockText);
    $confirmationBlockText = str_replace('{$url_confirm_and_not_receive}', $url_confirm_and_not_receive, $confirmationBlockText);
    $messageHtml[$cid] = removeDelim(strip_tags(str_replace("#CONFIRMATION_BLOCK", $confirmationBlockHtml, $msg), '<p><div><span><a><b><u><i><strong><table><tr><td><th>'));
    $messageText[$cid] = convertHtmlToText(str_replace("#CONFIRMATION_BLOCK", $confirmationBlockText, $msg));
  }

  // creating new mailings
  foreach ($campaigns as $cid) {
    $mailingName = date('Y-m-d').'-Reminder--campaign_id='.$cid; // todo change to internal name of campaign
    $params = array(
      'name' => $mailingName,
      'subject' => $subject[$cid],
      'body_text' => $messageText[$cid],
      'body_html' => $messageHtml[$cid],
      'created_id' => 2, // todo change to admin :-)
      'created_date' => date('YmdHis'),
      'campaign_id' => $cid,
      'mailing_type' => 'standalone',
      'unsubscribe_id' => 5,
      'resubscribe_id' => 6,
      'optout_id' => 7,
      'open_tracking' => 1,
      'url_tracking' => 1,
      'from_name' => $email[$cid]['from_name'],
      'from_email' => $email[$cid]['from_email'],
    );
    $mailing = new CRM_Mailing_BAO_Mailing();
    $mm = $mailing->add($params);

    $params = array(
      'mailing_id' => $mm->id,
      'group_type' => 'Exclude',
      'entity_table' => CRM_Contact_BAO_Group::getTableName(),
      'values' => array(array('entity_id' => $groupId)),
    );
    $result = civicrm_api3('mailing_group', 'replace', $params);

    $includeGroupName = 'Reminder--CAMP_ID_'.$cid;
    $params = array(
      'sequential' => 1,
      'title' => $includeGroupName,
      'group_type' => CRM_Core_DAO::VALUE_SEPARATOR . '2' . CRM_Core_DAO::VALUE_SEPARATOR, // mailing type
      'visibility' => 'User and User Admin Only',
      'source' => 'speakcivi',
    );
    $result = civicrm_api3('Group', 'create', $params);
    $includeGroupId = $result['id'];

    foreach ($contacts[$cid] as $contactId) {
      $params = array(
        'sequential' => 1,
        'group_id' => $includeGroupId,
        'contact_id' => $contactId,
        'status' => "Added",
      );
      $result = civicrm_api3('GroupContact', 'create', $params);
    }

    $params = array(
      'mailing_id' => $mm->id,
      'group_type' => 'Include',
      'entity_table' => CRM_Contact_BAO_Group::getTableName(),
    'values' => array(array('entity_id' => $includeGroupId)),
    );
    $result = civicrm_api3('mailing_group', 'replace', $params);

  }


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
 * Prepare clean url for Facebook sharing
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
 * Remove delim code from string (confirmation block)
 * @param string $str
 *
 * @return mixed
 */
function removeDelim($str) {
  $first = strpos($str, '{ldelim}');
  $last = strrpos($str, '{rdelim}');
  $str = substr_replace($str, '', $first, $last-$first+8);
  return $str;
}


/**
 * Parse sender email fields as from name and email separately
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
