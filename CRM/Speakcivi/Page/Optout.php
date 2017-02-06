<?php

require_once 'CRM/Core/Page.php';

class CRM_Speakcivi_Page_Optout extends CRM_Speakcivi_Page_Post {
  function run() {
    $this->setActivityStatusIds();
    $this->setValues();

    $contactParams = array(
      'is_opt_out' => 1,
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

    $aids = $this->findActivitiesIds($this->activityId, $this->campaignId, $this->contactId);
    $this->setActivitiesStatuses($this->activityId, $aids, 'optout', $location);

    if ($redirect) {
      CRM_Utils_System::redirect($redirect);
    }

    $country = $this->getCountry($this->campaignId);
    $url = "{$country}/post_optout";
    CRM_Utils_System::redirect($url);
  }
}
