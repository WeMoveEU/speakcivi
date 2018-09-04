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
    $contactParams = array(
      'is_opt_out' => 0,
      'do_not_email' => 0,
    );

    $groupId = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'group_id');
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

    $email = CRM_Speakcivi_Logic_Contact::getEmail($this->contactId);
    if ($email['on_hold'] != 0) {
      CRM_Speakcivi_Logic_Contact::unholdEmail($email['email_id']);
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
    $consent->utmSource = $this->utmSource;
    $consent->utmMedium = $this->utmMedium;
    $consent->utmCampaign = $this->utmCampaign;
    if ($consentIds) {
      foreach ($consentIds as $id) {
        list($consentVersion, $language) = explode('-', $id);
        $consent->version = $consentVersion;
        $consent->language = $language;
        CRM_Speakcivi_Logic_Activity::dpa($consent, $this->contactId, $this->campaignId, 'Completed');
      }
    }
    else {
      $consent->version = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'gdpr_privacy_pack_version');
      $consent->language = $country;
      CRM_Speakcivi_Logic_Activity::dpa($consent, $this->contactId, $this->campaignId, 'Completed');
    }

    $activityStatus = 'optin'; // default status: Completed new member
    $aids = $this->findActivitiesIds($this->activityId, $this->campaignId, $this->contactId);
    $this->setActivitiesStatuses($this->activityId, $aids, $activityStatus);

    $speakcivi = new CRM_Speakcivi_Page_Speakcivi();
    $speakcivi->sendConfirm($email['email'], $this->contactId, $this->activityId, $this->campaignId, FALSE, FALSE, 'new_member');

    $context = array(
      'drupal_language' => $country,
      'contact_id' => $this->contactId,
      'contact_checksum' => CRM_Contact_BAO_Contact_Utils::generateChecksum($this->contactId),
    );
    $url = $this->determineRedirectUrl('post_confirm', $country, $redirect, $context);
    CRM_Utils_System::redirect($url);
  }

}
