<?php

class CRM_Speakcivi_Logic_Cache_Country extends CRM_Speakcivi_Logic_Cache {

  const TYPE_COUNTRY = 'country';

  public static function reset() {
    $params = array(
      'sequential' => 1,
      'return' => 'id,iso_code',
      'options' => array('limit' => 0),
    );
    $result = civicrm_api3('Country', 'get', $params);
    $countries = array();
    foreach ($result['values'] as $country) {
      $countries[$country['iso_code']] = $country['id'];
    }
    self::set(self::TYPE_COUNTRY, 0, $countries);
    return $countries;
  }

  /**
   * Get country id by iso code.
   *
   * @param int $iso_code civicrm_campaign.id
   *
   * @return int
   */
  public static function getCountryId($iso_code) {
    if ($cache = self::get(self::TYPE_COUNTRY, 0)) {
      return CRM_Utils_Array::value($iso_code, $cache[self::TYPE_COUNTRY], 0);
    }
    $countries = self::reset();
    return CRM_Utils_Array::value($iso_code, $countries, 0);
  }

  public static function getCountryCode($id) {
    if ($cache = self::get(self::TYPE_COUNTRY, 0)) {
      return array_search($id, $cache[self::TYPE_COUNTRY]);
    }
    $countries = self::reset();
    return array_search($id, $countries);
  }
}
