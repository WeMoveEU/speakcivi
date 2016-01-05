<?php

require_once 'CRM/Core/Page.php';

class CRM_Speakcivi_Page_Confirm extends CRM_Speakcivi_Page_Post {
  function run() {
    $this->setValues();

    $this->setIsOptOut($this->contact_id, 0);

    $group_id = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'group_id');
    $this->setGroupStatus($this->contact_id, $group_id);

    if ($this->campaign_id) {
      $campaign = new CRM_Speakcivi_Logic_Campaign($this->campaign_id);
      $locale = $campaign->getLanguage();
      $language = substr($locale, 0, 2);
      $this->setLanguageGroup($this->contact_id, $language);
      $this->setLanguageTag($this->contact_id, $language);
    }

    $aids = $this->findActivitiesIds($this->activity_id, $this->campaign_id, $this->contact_id);
    $this->setActivitiesStatuses($this->activity_id, $aids, 'Completed');

    $country = $this->getCountry($this->campaign_id);
    $url = "{$country}/post_confirm";
    CRM_Utils_System::redirect($url);
  }
}
