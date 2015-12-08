<?php

require_once 'CRM/Core/Page.php';

class CRM_Speakcivi_Page_Optout extends CRM_Speakcivi_Page_Post {
  function run() {
    $this->setValues();

    $params_contact = array(
      'sequential' => 1,
      'id' => $this->contact_id,
      'is_opt_out' => 1,
    );
    $result = civicrm_api3('Contact', 'create', $params_contact);

    $group_id = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'group_id');
    $this->setGroupStatus($this->contact_id, $group_id);

    $this->setLanguageGroup($this->contact_id, $this->campaign_id);

    $aids = $this->findActivitiesIds($this->activity_id, $this->campaign_id, $this->contact_id);
    $this->setActivitiesStatuses($this->activity_id, $aids, 'optout');

    $country = $this->getCountry($this->campaign_id);
    $url = "{$country}/post_optout";
    CRM_Utils_System::redirect($url);
  }
}
