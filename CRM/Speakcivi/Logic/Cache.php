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
    if (self::isOld($cache['timestamp'])) {
      // I'm dubious this works
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
