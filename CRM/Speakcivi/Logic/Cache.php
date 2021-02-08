<?php

class CRM_Speakcivi_Logic_Cache {

  const KEY_PREFIX = 'speakcivi-cache-';

  private static $dateFormat = 'YmdHis';


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
    if (self::isOld($cache['expires'])) {
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
  protected  static function set($type, $id, $object, $ttl = 3600) {
    $key = self::KEY_PREFIX . $type . $id;

    $now = new DateTime('now');
    $now->add(new DateInterval("PT" . $ttl . "S"));

    $value = array(
      $type => $object,
      'expires' => $now->format(self::$dateFormat)
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
  private static function isOld($expires) {
    # old if expires in the past, less than now
    return DateTime::createFromFormat(self::$dateFormat, $expires) < new Datetime("now");
  }
}
