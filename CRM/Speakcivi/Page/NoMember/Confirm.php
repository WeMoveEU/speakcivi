<?php

require_once 'CRM/Core/Page.php';

class CRM_Speakcivi_Page_NoMember_Confirm extends CRM_Speakcivi_Page_Post {
  public function run() {
    $this->setActivityStatusIds();
    $this->setValues();

    $noMemberGroupId = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'no_member_group_id');
    $activityStatus = 'optin'; // default status: Completed new member
    if (!$this->isGroupContactAdded($this->contactId, $noMemberGroupId)) {
      $this->setGroupContactAdded($this->contactId, $noMemberGroupId);
    }
    else {
      $activityStatus = 'Completed'; // Completed existing member
    }

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
    );
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
    $speakcivi->sendConfirm($email['email'], $this->contactId, $this->activityId, $this->campaignId, FALSE, TRUE, 'new_member');

    $redirect = '';
    if ($this->campaignId) {
      $campaign = new CRM_Speakcivi_Logic_Campaign($this->campaignId);
      $redirect = $campaign->getRedirectConfirm();
    }
    $url = $this->determineRedirectUrl('post_confirm', $country, $redirect);
    CRM_Utils_System::redirect($url);
  }

}
