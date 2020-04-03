<?php

require_once 'CRM/Core/Page.php';

class CRM_Speakcivi_Page_Consentc extends CRM_Speakcivi_Page_Post {

  /**
   * @return null|void
   * @throws \CiviCRM_API3_Exception
   */
  public function run() {
    $this->setValues();
    $this->setConsentStatus('Confirmed');
    $contactParams = array(
      'is_opt_out' => 0,
    );
    CRM_Speakcivi_Logic_Contact::set($this->contactId, $contactParams);

    $lang = str_replace('en', '', $this->language);
    $url = $this->determineRedirectUrl('thank-you-for-your-confirmation', $lang, '');
    CRM_Utils_System::redirect($url);
  }

}
