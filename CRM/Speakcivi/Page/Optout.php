<?php

require_once 'CRM/Core/Page.php';

class CRM_Speakcivi_Page_Optout extends CRM_Speakcivi_Page_Post {

  /**
   * @return null|void
   * @throws \CiviCRM_API3_Exception
   */
  public function run() {
    $this->setActivityStatusIds();
    $this->setValues();

    $contactParams = array(
      'is_opt_out' => 1,
      CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_date') => '',
      CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_version') => '',
      CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_language') => '',
      CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_utm_source') => '',
      CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_utm_medium') => '',
      CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_utm_campaign') => '',
      CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_campaign_id') => '',
    );

    $groupId = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'group_id');
    $location = '';
    if ($this->isGroupContactAdded($this->contactId, $groupId)) {
      $this->setGroupContactRemoved($this->contactId, $groupId);
      $location = 'removed from Members after optout link';
      if (CRM_Speakcivi_Cleanup_Leave::hasJoins($this->contactId)) {
        CRM_Speakcivi_Logic_Activity::leave($this->contactId, 'confirmation_link', $this->campaignId, $this->activityId, '', 'Added by SpeakCivi Optout');
      }
    }

    $redirect = '';
    if ($this->campaignId) {
      $campaign = new CRM_Speakcivi_Logic_Campaign($this->campaignId);
      $locale = $campaign->getLanguage();
      $redirect = $campaign->getRedirectOptout();
      $language = substr($locale, 0, 2);
      $this->setLanguageTag($this->contactId, $language);
    }

    CRM_Speakcivi_Logic_Contact::set($this->contactId, $contactParams);
    $country = $this->getCountry($this->campaignId);

    $consentIds = $this->getConsentIds($this->campaignId);
    $consent = new CRM_Speakcivi_Logic_Consent();
    $consent->createDate = date('YmdHis');
    if ($consentIds) {
      foreach ($consentIds as $id) {
        list($consentVersion, $language) = explode('-', $id);
        $consent->version = $consentVersion;
        $consent->language = $language;
        $consent->utmSource = $this->utmSource;
        $consent->utmMedium = $this->utmMedium;
        $consent->utmCampaign = $this->utmCampaign;
        CRM_Speakcivi_Logic_Activity::dpa($consent, $this->contactId, $this->campaignId, 'Cancelled');
      }
    }
    else {
      $consent->version = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'gdpr_privacy_pack_version');
      $consent->language = $country;
      $consent->utmSource = $this->utmSource;
      $consent->utmMedium = $this->utmMedium;
      $consent->utmCampaign = $this->utmCampaign;
      CRM_Speakcivi_Logic_Activity::dpa($consent, $this->contactId, $this->campaignId, 'Cancelled');
    }

    $aids = $this->findActivitiesIds($this->activityId, $this->campaignId, $this->contactId);
    $this->setActivitiesStatuses($this->activityId, $aids, 'optout', $location);

    $url = $this->determineRedirectUrl('post_optout', $country, $redirect);
    CRM_Utils_System::redirect($url);
  }

}
