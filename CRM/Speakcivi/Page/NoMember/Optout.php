<?php

require_once 'CRM/Core/Page.php';

class CRM_Speakcivi_Page_NoMember_Optout extends CRM_Speakcivi_Page_Post {
  function run() {
    $this->setActivityStatusIds();
    $this->setValues();

    $noMemberGroupId = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'no_member_group_id');
    $location = '';
    if ($this->isGroupContactAdded($this->contactId, $noMemberGroupId)) {
      $this->setGroupContactRemoved($this->contactId, $noMemberGroupId);
      $location = 'removed from NoMembers after optout link';
    }

    $contactParams = array(
      'is_opt_out' => 1,
    );
    CRM_Speakcivi_Logic_Contact::set($this->contactId, $contactParams);

    $aids = $this->findActivitiesIds($this->activityId, $this->campaignId, $this->contactId);
    $this->setActivitiesStatuses($this->activityId, $aids, 'optout', $location);

    $country = $this->getCountry($this->campaignId);
    $this->setConsentStatus('Rejected');

    $redirect = '';
    if ($this->campaignId) {
      $campaign = new CRM_Speakcivi_Logic_Campaign($this->campaignId);
      $redirect = $campaign->getRedirectOptout();
    }
    $url = $this->determineRedirectUrl('post_optout', $country, $redirect);
    CRM_Utils_System::redirect($url);
  }
}
