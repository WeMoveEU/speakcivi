<?php

require_once 'CRM/Core/Page.php';

class CRM_Speakcivi_Page_Consentc extends CRM_Speakcivi_Page_Post {

  /**
   * @return null|void
   * @throws \CiviCRM_API3_Exception
   */
  public function run() {
    $this->setValues();

    $consent = new CRM_Speakcivi_Logic_Consent();
    $consent->version = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'gdpr_privacy_pack_version');
    $consent->createDate = date('YmdHis');
    $consent->utmSource = $this->utmSource;
    $consent->utmMedium = $this->utmMedium;
    $consent->utmCampaign = $this->utmCampaign;
    $consent->language = $this->language;
    CRM_Speakcivi_Logic_Activity::dpa($consent, $this->contactId, $this->campaignId, 'Completed');

    $contactParams = array(
      'is_opt_out' => 0,
    );
    CRM_Speakcivi_Logic_Contact::set($this->contactId, $contactParams);

    $lang = str_replace('en', '', $this->language);
    $url = $this->determineRedirectUrl('thank-you-for-your-confirmation', $lang, '');
    CRM_Utils_System::redirect($url);
  }

}
