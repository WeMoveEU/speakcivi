<?php

function civicrm_api3_speakcivi_update_stats ($params) {
 $config = CRM_Core_Config::singleton();
  //run the query
  $sql = file_get_contents(dirname( __FILE__ ) .'/sql/update.sql', true);
die ($sql);
  return civicrm_api3_create_success(array("query"=>$sql), $params);
  CRM_Utils_File::sourceSQLFile($config->dsn, $sql, NULL, true);

}

function _civicrm_api3_speakcivi_sendconfirm_spec(&$params) {
  $params['toEmail']['api.required'] = 1;
  $params['contact_id']['api.required'] = 1;
  $params['campaign_id']['api.required'] = 1;
  $params['from']['api.default'] = html_entity_decode(CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'from'));
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
  $locales = getLocale($locale);
  $confirmation_block_html = $template->fetch('../templates/CRM/Speakcivi/Page/ConfirmationBlock.'.$locales['html'].'.html.tpl');
  $confirmation_block_text = $template->fetch('../templates/CRM/Speakcivi/Page/ConfirmationBlock.'.$locales['text'].'.text.tpl');

  /* SHARING_BLOCK */
  $template->clearTemplateVars();
  $template->assign('url_campaign', $campaignObj->getUrlCampaign());
  $template->assign('url_campaign_fb', prepareCleanUrl($campaignObj->getUrlCampaign()));
  $template->assign('utm_campaign', $campaignObj->getUtmCampaign());
  $template->assign('share_facebook', CRM_Speakcivi_Tools_Dictionary::getShareFacebook($locale));
  $template->assign('share_twitter', CRM_Speakcivi_Tools_Dictionary::getShareTwitter($locale));
  $template->assign('twitter_share_text', urlencode($campaignObj->getTwitterShareText()));
  $sharing_block_html = $template->fetch('../templates/CRM/Speakcivi/Page/SharingBlock.html.tpl');

  $template->clearTemplateVars();
  $template->assign('contact', $contact);
  $message = $template->fetch('string:'.$message);

  $message_html = str_replace("#CONFIRMATION_BLOCK", html_entity_decode($confirmation_block_html), $message);
  $message_text = str_replace("#CONFIRMATION_BLOCK", html_entity_decode($confirmation_block_text), $message);
  $message_html = str_replace("#SHARING_BLOCK", html_entity_decode($sharing_block_html), $message_html);
  $message_text = str_replace("#SHARING_BLOCK", html_entity_decode($sharing_block_html), $message_text);

  $params['html'] = $message_html;
  $params['text'] = convertHtmlToText($message_text);
  $sent = CRM_Utils_Mail::send($params);
  return civicrm_api3_create_success($sent, $params);
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
