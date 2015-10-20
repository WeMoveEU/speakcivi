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
  $cgid = $params['contact_id'];
  $aid = $params['activity_id'];
  $campaign_id = $params['campaign_id'];
  $hash = sha1(CIVICRM_SITE_KEY . $cgid);
  $url = CRM_Utils_System::url('civicrm/speakcivi/confirm',
    "id=$cgid&aid=$aid&cid=$campaign_id&hash=$hash&utm_source=civicrm&utm_medium=email&utm_campaign=speakout_confirm", true);
  $params['subject'] = $dao->subject;
  $params['text'] = str_replace("#speakout_url_confirm", $url, $dao->text);
  $params['html'] = str_replace("#speakout_url_confirm", $url, $dao->html);
  $params['format'] = $dao->format;
  $dao->free();

  $sent = CRM_Utils_Mail::send($params);

  return civicrm_api3_create_success($sent, $params);
}
