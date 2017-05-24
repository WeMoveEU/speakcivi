<?php

class CRM_Speakcivi_Logic_Cache_Country extends CRM_Speakcivi_Logic_Cache {

  const TYPE_COUNTRY = 'country';

  /**
   * Get country id by iso code.
   *
   * @param int $iso_code civicrm_campaign.id
   *
   * @return int
   */
  public static function getCountryId($iso_code) {
    if ($cache = self::get(self::TYPE_COUNTRY, 0)) {
      echo "get\n";
      return $cache[self::TYPE_COUNTRY][$iso_code];
    }
    echo "set\n";
    $params = array(
      'sequential' => 1,
      'return' => 'id,iso_code',
    );
    $result = civicrm_api3('Country', 'get', $params);
    $countries = array();
    foreach ($result['values'] as $country) {
      $countries[$country['iso_code']] = $country['id'];
    }
    self::set(self::TYPE_COUNTRY, 0, $countries);
    return CRM_Utils_Array::value($iso_code, $countries, 0);
  }
}
