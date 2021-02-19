<?php

class CRM_Speakcivi_Logic_Cache_Campaign extends CRM_Speakcivi_Logic_Cache {

  const TYPE_CAMPAIGN_LOCAL = 'campaign-local';

  const TYPE_CAMPAIGN_EXTERNAL = 'campaign-external';


  /**
   * Get campaign by local id.
   *
   * @param int $id civicrm_campaign.id
   *
   * @return array
   */
  public static function getCampaignByLocalId($id) {
    if ($cache = self::get(self::TYPE_CAMPAIGN_LOCAL, $id)) {
      return $cache[self::TYPE_CAMPAIGN_LOCAL];
    }

    $campaignObj = new CRM_Speakcivi_Logic_Campaign();
    $campaignObj->campaign = $campaignObj->getCampaign($id, TRUE);
    self::set(self::TYPE_CAMPAIGN_LOCAL, $id, $campaignObj->campaign);
    return $campaignObj->campaign;
  }


  /**
   * Get campaign by external id.
   *
   * @return array
   */
  public static function getCampaignByExternalId($external_id, $action_technical_type) {
    if ($cache = self::get(self::TYPE_CAMPAIGN_EXTERNAL, $external_id)) {
      return $cache[self::TYPE_CAMPAIGN_EXTERNAL];
    }
    $campaignObj = new CRM_Speakcivi_Logic_Campaign();
    $campaignObj->campaign = $campaignObj->getCampaign($external_id);
    $newCampaign = FALSE;
    if (!$campaignObj->isValidCampaign($campaignObj->campaign)) {
      $campaignObj->campaign = $campaignObj->setCampaign($external_id, $campaignObj->campaign, $action_technical_type);
      $newCampaign = TRUE;
      CRM_Core_PseudoConstant::flush();
      $campaignObj->campaign = $campaignObj->getCampaign($external_id, false, false);
    }
    // fixme move limit to settings
    $limit = 20;
    $nbActivities = CRM_Utils_Array::value('api.Activity.getcount', $campaignObj->campaign, 0);
    // if the campaign is not new and has 0 activities, it's a parent that will never have any activity
    // => cache
    if ((!$newCampaign && $nbActivities === 0) || $nbActivities >= $limit) {
      self::set(self::TYPE_CAMPAIGN_EXTERNAL, $external_id, $campaignObj->campaign);
    }
    return $campaignObj->campaign;
  }

  public static function resetLocalCampaignCache($civicrm_campaign_id, $civicrm_campaign) {
    Civi::cache()->flush();
    /*
     * self::set doesn't work to reset a value. I don't know why. The value 
     * $civicrm_campaign may not be the right value to save here - calling getCampaign 
     * might be needed. 
     *
     * If you figure it out, please tell me at aaron@wemove.eu
     *
    self::set(
        self::TYPE_CAMPAIGN_LOCAL,
        $civicrm_campaign_id, 
        $civicrm_campaign
      );
     *
     *
     */
  }

}
