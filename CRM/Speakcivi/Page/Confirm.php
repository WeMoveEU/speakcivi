<?php

require_once 'CRM/Core/Page.php';

class CRM_Speakcivi_Page_Confirm extends CRM_Speakcivi_Page_Post {

  /**
   * @return null|void
   * @throws \CiviCRM_API3_Exception
   */
  public function run() {
    $this->setActivityStatusIds();
    $this->setValues();

    $country = $this->getCountry($this->campaignId);
    $consentIds = $this->getConsentIds($this->campaignId);
    // fixme which consent should be set at contact level?
    // fixme assumption: first
    if ($consentIds) {
      $consentVersion = explode('-', $consentIds[0])[0];
    }
    else {
      $consentVersion = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'gdpr_privacy_pack_version');
    }
    $contactParams = array(
      'is_opt_out' => 0,
      CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_date') => date('Y-m-d'),
      CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_version') => $consentVersion,
      CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_language') => strtoupper($country),
      CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_utm_source') => $this->utmSource,
      CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_utm_medium') => $this->utmMedium,
      CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_utm_campaign') => $this->utmCampaign,
      CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_campaign_id') => $this->campaignId,
    );

    $groupId = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'group_id');
    $activityStatus = 'optin'; // default status: Completed new member
    if (!$this->isGroupContactAdded($this->contactId, $groupId)) {
      if (!CRM_Speakcivi_Logic_Activity::hasJoin($this->activityId)) {
        $joinId = CRM_Speakcivi_Logic_Activity::join($this->contactId, 'confirmation_link', $this->campaignId, $this->activityId);
        $fields = [
          'source' => $this->utmSource,
          'medium' => $this->utmMedium ,
          'campaign' => $this->utmCampaign,
        ];
        CRM_Speakcivi_Logic_Activity::setSourceFields($joinId, $fields);
      }
      $this->setGroupContactAdded($this->contactId, $groupId);
    }
    else {
      $activityStatus = 'Completed'; // Completed existing member
    }

    $redirect = '';
    if ($this->campaignId) {
      $campaign = new CRM_Speakcivi_Logic_Campaign($this->campaignId);
      $locale = $campaign->getLanguage();
      $redirect = $campaign->getRedirectConfirm();
      $language = substr($locale, 0, 2);
      $rlg = $this->setLanguageGroup($this->contactId, $language);
      $this->setLanguageTag($this->contactId, $language);
      if ($rlg == 1) {
        $contactParams['preferred_language'] = $locale;
      }
    }

    CRM_Speakcivi_Logic_Contact::set($this->contactId, $contactParams);

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
        CRM_Speakcivi_Logic_Activity::dpa($consent, $this->contactId, $this->campaignId, 'Completed');
      }
    }
    else {
      $consent->version = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'gdpr_privacy_pack_version');
      $consent->language = $country;
      $consent->utmSource = $this->utmSource;
      $consent->utmMedium = $this->utmMedium;
      $consent->utmCampaign = $this->utmCampaign;
      CRM_Speakcivi_Logic_Activity::dpa($consent, $this->contactId, $this->campaignId, 'Completed');
    }

    $aids = $this->findActivitiesIds($this->activityId, $this->campaignId, $this->contactId);
    $this->setActivitiesStatuses($this->activityId, $aids, $activityStatus);

    $email = CRM_Speakcivi_Logic_Contact::getEmail($this->contactId);
    $speakcivi = new CRM_Speakcivi_Page_Speakcivi();
    $speakcivi->sendConfirm($email, $this->contactId, $this->activityId, $this->campaignId, FALSE, FALSE, 'new_member');

    $context = array(
      'drupal_language' => $country,
      'contact_id' => $this->contactId,
      'contact_checksum' => CRM_Contact_BAO_Contact_Utils::generateChecksum($this->contactId),
    );
    $url = $this->determineRedirectUrl('post_confirm', $country, $redirect, $context);
    CRM_Utils_System::redirect($url);
  }

}
