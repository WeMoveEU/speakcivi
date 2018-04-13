<?php

require_once 'CRM/Core/Page.php';

class CRM_Speakcivi_Page_Confirm extends CRM_Speakcivi_Page_Post {
  function run() {
    $this->setActivityStatusIds();
    $this->setValues();

    $country = $this->getCountry($this->campaignId);
    $consentVersion = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'gdpr_privacy_pack_version')
      . '-' . $country;
    $contactParams = array(
      'is_opt_out' => 0,
      CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_date') => date('Y-m-d'),
      CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_version') => $consentVersion,
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
    } else {
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

    $aids = $this->findActivitiesIds($this->activityId, $this->campaignId, $this->contactId);
    $this->setActivitiesStatuses($this->activityId, $aids, $activityStatus);

    $email = CRM_Speakcivi_Logic_Contact::getEmail($this->contactId);
    $speakcivi = new CRM_Speakcivi_Page_Speakcivi();
    $speakcivi->sendConfirm($email, $this->contactId, $this->activityId, $this->campaignId, false, false, 'new_member');

    $context = array(
      'drupal_language' => $country, 
      'contact_id' => $this->contactId,
      'contact_checksum' => CRM_Contact_BAO_Contact_Utils::generateChecksum($this->contactId)
    );
    $url = $this->determineRedirectUrl('post_confirm', $country, $redirect, $context);
    CRM_Utils_System::redirect($url);
  }
}
