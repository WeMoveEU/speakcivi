<?php

require_once 'CRM/Core/Page.php';

class CRM_Speakcivi_Page_Lang extends CRM_Speakcivi_Page_Post {

  private $urlLanguageForm = '/language-change';

  private $thankYouPage = 'language-change-confirmation';

  /**
   * @return null|void
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function run() {
    $hash = CRM_Utils_Request::retrieve('hash', 'String', $this, TRUE);
    $language = CRM_Utils_Request::retrieve('language', 'String', $this, FALSE);
    if (!CRM_Speakcivi_Logic_Language::isValid($language)) {
      CRM_Utils_System::redirect($this->urlLanguageForm);
    }

    $params = [
      'hash' => $hash,
      'language' => $language,
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
