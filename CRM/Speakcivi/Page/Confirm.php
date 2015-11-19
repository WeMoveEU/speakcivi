<?php

require_once 'CRM/Core/Page.php';

class CRM_Speakcivi_Page_Confirm extends CRM_Speakcivi_Page_Post {
  function run() {
    $this->setValues();

    $group_id = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'group_id');
    $this->setGroupStatus($this->contact_id, $group_id);

    $this->setActivityStatus($this->activity_id, 'Completed');

    $country = $this->getCountry($this->campaign_id);
    $url = "{$country}/post_confirm";
    CRM_Utils_System::redirect($url);
  }
}
