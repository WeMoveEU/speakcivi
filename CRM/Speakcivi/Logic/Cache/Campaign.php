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
   * @param object $param
   *
   * @return array
   */
  public static function getCampaignByExternalId($param) {
    if ($cache = self::get(self::TYPE_CAMPAIGN_EXTERNAL, $param->external_id)) {
      return $cache[self::TYPE_CAMPAIGN_EXTERNAL];
    }
    $campaignObj = new CRM_Speakcivi_Logic_Campaign();
    $campaignObj->campaign = $campaignObj->getCampaign($param->external_id);
    if (!$campaignObj->isValidCampaign($campaignObj->campaign)) {
      $campaignObj->campaign = $campaignObj->setCampaign($param->external_id, $campaignObj->campaign, $param);
      $campaignObj->campaign = $campaignObj->getCampaign($param->external_id, false, false);
    }
    // fixme move limit to settings
    $limit = 20;
    if ($campaignObj->campaign['api.Activity.getcount'] >= $limit) {
      self::set(self::TYPE_CAMPAIGN_EXTERNAL, $param->external_id, $campaignObj->campaign);
    }
    return $campaignObj->campaign;
  }
}
