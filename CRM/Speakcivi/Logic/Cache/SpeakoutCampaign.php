<?php

class CRM_Speakcivi_Logic_Cache_SpeakoutCampaign extends CRM_Speakcivi_Logic_Cache
{

    const CACHE_PREFIX = '-speakout-';

    public static function getCampaign($speakoutDomain, $speakoutCampaignId)
    {
        if ($speakout_campaign = self::get(
            self::CACHE_PREFIX,
            "$speakoutDomain:$speakoutCampaignId"
        )) {
            return $speakout_campaign;
        }

        $url = "https://$speakoutDomain/api/v1/campaigns/$speakoutCampaignId";
        $user = CIVICRM_SPEAKOUT_USERS[$speakoutDomain];
        $auth = $user['email'] . ':' . $user['password'];
        $speakout_campaign = (array)json_decode(fetchSpeakoutContent($url, $auth));

        self::set(self::CACHE_PREFIX, "$speakoutDomain:$speakoutCampaignId", $speakout_campaign, 300); # TODO: Set in configuration

        return $speakout_campaign;
    }
}
