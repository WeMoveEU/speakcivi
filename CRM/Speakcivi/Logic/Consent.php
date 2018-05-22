<?php

class CRM_Speakcivi_Logic_Consent {
  public $isPublic;
  public $version;
  public $language;
  public $date;
  public $level;
  public $method;
  public $methodOption;

  /**
   * @param $param
   *
   * @return array
   */
  public static function prepareFields($param) {
    $consents = [];
    if (property_exists($param, 'consents')) {
      foreach ($param->consents as $consent) {
        list($consentVersion, $consentLanguage) = explode('-', $consent->public_id);
        $cd = new DateTime(substr($param->create_dt, 0, 10));
        $c = new self();
        $c->isPublic = $consent->is_public;
        $c->version = $consentVersion;
        $c->language = $consentLanguage;
        $c->date = $cd->format('Y-m-d');
        $c->level = $consent->consent_level;
        $c->method = $consent->consent_method;
        $c->methodOption = $consent->consent_method_option;
        $consents[] = $c;
      }
    }
    return $consents;
  }

}
