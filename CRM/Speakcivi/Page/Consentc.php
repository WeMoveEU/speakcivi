<?php

require_once 'CRM/Core/Page.php';

class CRM_Speakcivi_Page_Consentc extends CRM_Speakcivi_Page_Post {

  /**
   * @return null|void
   * @throws \CiviCRM_API3_Exception
   */
  public function run() {
    $this->setValues();
    $consentVersion = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'gdpr_privacy_pack_version');
    $contactParams = array(
      'is_opt_out' => 0,
      CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_date') => date('Y-m-d'),
      CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_version') => $consentVersion,
      CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_language') => strtoupper($this->language),
      CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_utm_source') => $this->utmSource,
      CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_utm_medium') => $this->utmMedium,
      CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_utm_campaign') => $this->utmCampaign,
    );
    CRM_Speakcivi_Logic_Contact::set($this->contactId, $contactParams);

    $lang = str_replace('en', '', $this->language);
    $url = $this->determineRedirectUrl('thank-you-for-your-confirmation', $lang, '');
    CRM_Utils_System::redirect($url);
  }

}
