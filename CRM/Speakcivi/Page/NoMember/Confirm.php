<?php

require_once 'CRM/Core/Page.php';

class CRM_Speakcivi_Page_NoMember_Confirm extends CRM_Speakcivi_Page_Post {
  function run() {
    $this->setActivityStatusIds();
    $this->setValues();

    $noMemberGroupId = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'no_member_group_id');
    $activityStatus = 'optin'; // default status: Completed new member
    if (!$this->isGroupContactAdded($this->contactId, $noMemberGroupId)) {
      $this->setGroupContactAdded($this->contactId, $noMemberGroupId);
    } else {
      $activityStatus = 'Completed'; // Completed existing member
    }

    $contactParams = array(
      'is_opt_out' => 0,
    );
    CRM_Speakcivi_Logic_Contact::set($this->contactId, $contactParams);

    $aids = $this->findActivitiesIds($this->activityId, $this->campaignId, $this->contactId);
    $this->setActivitiesStatuses($this->activityId, $aids, $activityStatus);

    $email = CRM_Speakcivi_Logic_Contact::getEmail($this->contactId);
    $speakcivi = new CRM_Speakcivi_Page_Speakcivi();
    $speakcivi->sendConfirm($email, $this->contactId, $this->activityId, $this->campaignId, false, true, 'new_member');

    $country = $this->getCountry($this->campaignId);
    if ($this->campaignId) {
      $campaign = new CRM_Speakcivi_Logic_Campaign($this->campaignId);
      if ($redirect = $campaign->getRedirectConfirm()) {
        $redirect = str_replace('{$language}', $country, $redirect);
        CRM_Utils_System::redirect($redirect);
      }
    }
    $url = "{$country}/post_confirm";
    CRM_Utils_System::redirect($url);
  }
}
