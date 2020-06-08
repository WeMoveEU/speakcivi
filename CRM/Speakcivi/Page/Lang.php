<?php

require_once 'CRM/Core/Page.php';

class CRM_Speakcivi_Page_Lang extends CRM_Speakcivi_Page_Post {

  private $urlLanguageForm = '/form/preferred-language';

  private $thankYouPage = 'language-change-confirmation';

  /**
   * @return null|void
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function run() {
    $hash = CRM_Utils_Request::retrieve('hash', 'String', $this, TRUE);
    $language = CRM_Utils_Request::retrieve('language', 'String', $this, FALSE);
    $utmSource = CRM_Utils_Request::retrieve('utm_source', 'String', $this, FALSE);
    $utmMedium = CRM_Utils_Request::retrieve('utm_medium', 'String', $this, FALSE);
    $utmCampaign = CRM_Utils_Request::retrieve('utm_campaign', 'String', $this, FALSE);
    if (!CRM_Speakcivi_Logic_Language::isValid($language)) {
      CRM_Utils_System::redirect($this->urlLanguageForm);
    }

    $params = [
      'hash' => $hash,
      'language' => $language,
      'utm_source' => $utmSource,
      'utm_medium' => $utmMedium,
      'utm_campaign' => $utmCampaign,
    ];
    $result = civicrm_api3('WemoveLanguage', 'switch', $params);
    if ($result['is_error']) {
      CRM_Utils_System::redirect($this->urlLanguageForm);
    }

    $lang = substr($language, 0, 2);
    $url = $this->determineRedirectUrl($this->thankYouPage, $lang, '');
    CRM_Utils_System::redirect($url);
  }

}
