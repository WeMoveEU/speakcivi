<?php

class CRM_Speakcivi_Logic_Cache {
  public static function campaign($param) {
    $key = 'speakcivi-cachecampaign-' . $param->external_id;
    $cacheCampaign = Civi::cache()->get($key);
    if (!isset($cacheCampaign)) {
      $campaignObj = new CRM_Speakcivi_Logic_Campaign();
      $campaign = $campaignObj->getCampaign($param->external_id);
      $campaign = $campaignObj->setCampaign($param->external_id, $campaign, $param);
      $customFields = $campaignObj->getCustomFields($campaign['id']);
      $cacheCampaign = array(
        'campaign' => $campaign,
        'customFields' => $customFields,
      );
      Civi::cache()->set($key, $cacheCampaign);
    }
    return $cacheCampaign;
  }
}
