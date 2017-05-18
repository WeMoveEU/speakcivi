<?php

class CRM_Speakcivi_Logic_Cache {

  const KEY_PREFIX = 'speakcivi-cache-';

  private static $dateFormat = 'YmdHis';

  public static function campaign($param) {
    $key = 'speakcivi-cachecampaign-' . $param->external_id;
    $cacheCampaign = Civi::cache()->get($key);
    if (!isset($cacheCampaign) || self::isOld($cacheCampaign['timestamp'])) {
      $campaignObj = new CRM_Speakcivi_Logic_Campaign();
      $campaignObj->campaign = $campaignObj->getCampaign($param->external_id);
      $campaignObj->campaign = $campaignObj->setCampaign($param->external_id, $campaignObj->campaign, $param);
      $cacheCampaign = array(
        'campaign' => $campaignObj->campaign,
        'timestamp' => date(self::$dateFormat),
      );
      // fixme move limit to settings
      $limit = 20;
      if ($campaignObj->campaign['api.Activity.getcount'] >= $limit) {
        Civi::cache()->set($key, $cacheCampaign);
      }
    }
    return $cacheCampaign;
  }


  /**
   * Get array cache for given object.
   *
   * @param string $type
   * @param int $id
   *
   * @return mixed|null
   */
  protected static function get($type, $id) {
    $key = self::KEY_PREFIX . $type . $id;
    $cache = Civi::cache()->get($key);
    if (!isset($cache)) {
      return NULL;
    }
    if (self::isOld($cache['timestamp'])) {
      Civi::cache()->delete($key);
      return NULL;
    }
    return $cache;
  }


  /**
   * Set array cache for given object.
   *
   * @param string $type
   * @param int $id
   * @param mixed $object
   */
  protected  static function set($type, $id, $object) {
    $key = self::KEY_PREFIX . $type . $id;
    $value = array(
      $type => $object,
      'timestamp' => date(self::$dateFormat),
    );
    Civi::cache()->set($key, $value);
  }


  /**
   * Check if cache is old.
   *
   * @param string $timestamp
   *
   * @return bool
   */
  private static function isOld($timestamp) {
    $old = DateTime::createFromFormat(self::$dateFormat, $timestamp)
      ->modify('+1 hour')
      ->format(self::$dateFormat);
    return ($old < date(self::$dateFormat));
  }
}
