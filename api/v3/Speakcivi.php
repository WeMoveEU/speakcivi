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
  $language = $params['language'];
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
    $confirmation_block_html = $template->fetch('../templates/CRM/Speakcivi/Page/ConfirmationBlock.'.$language.'.html.tpl');
    $confirmation_block_text = $template->fetch('../templates/CRM/Speakcivi/Page/ConfirmationBlock.'.$language.'.text.tpl');
    $params['subject'] = getSubjectConfirm($language);
  } else {
    $params['subject'] = getSubjectImpact($language);
  }

  $params['html'] = str_replace("#CONFIRMATION_BLOCK", $confirmation_block_html, $dao->html);
  $params['text'] = str_replace("#CONFIRMATION_BLOCK", $confirmation_block_text, $dao->text);

  $params['format'] = $dao->format;
  $dao->free();

  $sent = CRM_Utils_Mail::send($params);

  return civicrm_api3_create_success($sent, $params);
}


// todo move dictionary to other better place
function getSubjectConfirm($language) {
  switch ($language) {
    case 'de_DE':
      return 'Sie sind fast fertig. Bitte bestätigen Sie Ihre Unterschrift';
      break;

    case 'fr_FR':
      return 'Vous avez presque terminé';
      break;

    case 'pl_PL':
      return 'Prawie skończone - potwierdź podpisanie petycji';
      break;

    default:
      return 'You are almost done - please confirm your action';
  }
}


// todo move dictionary to other better place
function getSubjectImpact($language) {
  switch ($language) {
    case 'de_DE':
      return 'Sie sind fast fertig. Bitte helfen Sie nun mit, diese Aktion weiterzuverbreiten.';
      break;

    case 'fr_FR':
      return 'Démultipliez votre impact';
      break;

    case 'pl_PL':
      return 'Prawie skończone - powiadom znajomych o petycji';
      break;

    default:
      return 'You are almost done - now multiply your impact';
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
    if (file_exists('../templates/CRM/Speakcivi/Page/ConfirmationBlock.'.$locale.'.html.tpl')) {
      $localeTab[$type] = $locale;
    }
  }
  return $localeTab;
}
