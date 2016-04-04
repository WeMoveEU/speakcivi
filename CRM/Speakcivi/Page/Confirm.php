<?php

require_once 'CRM/Core/Page.php';

class CRM_Speakcivi_Page_Confirm extends CRM_Speakcivi_Page_Post {
  function run() {
    $this->setValues();

    $this->setIsOptOut($this->contactId, 0);

    $groupId = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'group_id');
    $activityStatus = 'Completed'; // Completed existing member
    if (!$this->isGroupContactAdded($this->contactId, $groupId)) {
      if (!CRM_Speakcivi_Logic_Activity::hasJoin($this->activityId)) {
        CRM_Speakcivi_Logic_Activity::join($this->contactId, 'confirmation_link', $this->campaignId, $this->activityId);
      }
      $this->setGroupContactAdded($this->contactId, $groupId);
      $activityStatus = 'optin'; // Completed new member
    }

    if ($this->campaignId) {
      $campaign = new CRM_Speakcivi_Logic_Campaign($this->campaignId);
      $locale = $campaign->getLanguage();
      $language = substr($locale, 0, 2);
      $this->setLanguageGroup($this->contactId, $language);
      $this->setLanguageTag($this->contactId, $language);
    }

    $aids = $this->findActivitiesIds($this->activityId, $this->campaignId, $this->contactId);
    $this->setActivitiesStatuses($this->activityId, $aids, $activityStatus);

    $email = CRM_Speakcivi_Logic_Contact::getEmail($this->contactId);
    $speakcivi = new CRM_Speakcivi_Page_Speakcivi();
    $speakcivi->sendConfirm($email, $this->contactId, $this->activityId, $this->campaignId, false);

    $country = $this->getCountry($this->campaignId);
    $url = "{$country}/post_confirm";
    CRM_Utils_System::redirect($url);
  }
}
