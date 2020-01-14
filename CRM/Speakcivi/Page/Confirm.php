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

    $campaign = new CRM_Speakcivi_Logic_Campaign($this->campaignId);
    $contactParams = $this->getContactConsentParams($campaign);

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

    if ($campaign) {
      $locale = $campaign->getLanguage();
      $language = substr($locale, 0, 2);
      // HACK to avoid a DB call to retrieve country: at this stage it is known that the country is either DE or AT (because we are processing a confirm link)
      // It does not matter which, as long as it is not GB, so we just hardcode DE
      $rlg = $this->setLanguageGroup($this->contactId, $language, 'DE');
      $this->setLanguageTag($this->contactId, $language);
      if ($rlg == 1) {
        $contactParams['preferred_language'] = $locale;
      }
    }

    CRM_Speakcivi_Logic_Contact::set($this->contactId, $contactParams);
    $this->createConsentActivities($campaign);

    $activityStatus = 'optin'; // default status: Completed new member
    $aids = $this->findActivitiesIds($this->activityId, $this->campaignId, $this->contactId);
    $this->setActivitiesStatuses($this->activityId, $aids, $activityStatus);

    $speakcivi = new CRM_Speakcivi_Page_Speakcivi();
    $speakcivi->sendConfirm($email['email'], $this->contactId, $this->activityId, $this->campaignId, FALSE, FALSE, 'post-confirm');

    $this->redirect($campaign, 'post_confirm');
  }

}
