<?php

function _civicrm_api3_speakcivi_sendconfirm_spec(&$params) {
  $params['toEmail']['api.required'] = 1;
  $params['contact_id']['api.required'] = 1;
  $params['messageTemplateID']['api.default'] = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'default_template_id');
  $params['from']['api.default'] = html_entity_decode(CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'from'));
}

function civicrm_api3_speakcivi_sendconfirm($params) {
  $query = 'SELECT msg_subject subject, msg_text text, msg_html html, pdf_format_id format
                      FROM civicrm_msg_template mt
                      WHERE mt.id = %1 AND mt.is_default = 1';
  $sqlParams = array(1 => array($params['messageTemplateID'], 'String'));
  $dao = CRM_Core_DAO::executeQuery($query, $sqlParams);
  $dao->fetch();

  if (!$dao->N) {
    CRM_Core_Error::fatal(ts('No such message template: id=%1.', array(1 => $params['messageTemplateID'])));
  }

  $confirmation_block_html = '';
  $confirmation_block_text = '';
  $locale = $params['language'];
  $locales = getLocale($params['language']);
  if ($params['confirmation_block']) {
    $cgid = $params['contact_id'];
    $aid = $params['activity_id'];
    $campaign_id = $params['campaign_id'];
    $hash = sha1(CIVICRM_SITE_KEY . $cgid);
    $url_confirm_and_keep = CRM_Utils_System::url('civicrm/speakcivi/confirm',
      "id=$cgid&aid=$aid&cid=$campaign_id&hash=$hash&utm_source=civicrm&utm_medium=email&utm_campaign=speakout_confirm", true);
    $url_confirm_and_not_receive = CRM_Utils_System::url('civicrm/speakcivi/optout',
      "id=$cgid&aid=$aid&cid=$campaign_id&hash=$hash&utm_source=civicrm&utm_medium=email&utm_campaign=speakout_optout", true);

    $template = CRM_Core_Smarty::singleton();
    $template->assign('url_confirm_and_keep', $url_confirm_and_keep);
    $template->assign('url_confirm_and_not_receive', $url_confirm_and_not_receive);
    $confirmation_block_html = $template->fetch('../templates/CRM/Speakcivi/Page/ConfirmationBlock.'.$locales['html'].'.html.tpl');
    $confirmation_block_text = $template->fetch('../templates/CRM/Speakcivi/Page/ConfirmationBlock.'.$locales['text'].'.text.tpl');
    $params['subject'] = CRM_Speakcivi_Tools_Dictionary::getSubjectConfirm($locale);
  } else {
    $params['subject'] = CRM_Speakcivi_Tools_Dictionary::getSubjectImpact($locale);
  }

  $params['html'] = str_replace("#CONFIRMATION_BLOCK", html_entity_decode($confirmation_block_html), $dao->html);
  $params['text'] = str_replace("#CONFIRMATION_BLOCK", html_entity_decode($confirmation_block_text), $dao->text);

  $params['format'] = $dao->format;
  $dao->free();

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
