<?php

class CRM_Speakcivi_Logic_Consent {
  const STATUS_NOTPROVIDED = 'not_provided';
  const STATUS_ACCEPTED = 'explicit_opt_in';
  const STATUS_REJECTED = 'none_given';
  public $publicId;
  public $version;
  public $language;
  public $date;
  public $createDate;
  public $level;
  public $method;
  public $methodOption;
  public $utmSource;
  public $utmMedium;
  public $utmCampaign;

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
        $c = new self();
        $c->publicId = $consent->public_id;
        $c->version = $consentVersion;
        $c->language = $consentLanguage;
        $c->date = $param->create_dt;
        $c->createDate = $param->create_dt;
        $c->level = $consent->consent_level;
        $c->method = $consent->consent_method;
        $c->methodOption = $consent->consent_method_option;
        $c->utmSource = @$param->source->source;
        $c->utmMedium = @$param->source->medium;
        $c->utmCampaign = @$param->source->campaign;
        $consents[] = $c;
      }
    }
    return $consents;
  }

  /**
   * Check if at least one consent has explicit_opt_in level.
   *
   * @param $consents
   *
   * @return bool
   */
  public static function isExplicitOptIn($consents) {
    foreach ($consents as $consent) {
      if ($consent->level == 'explicit_opt_in') {
        return TRUE;
      }
    }
    return FALSE;

  }

  /**
   * Check if at least one consent has explicit_opt_in level.
   *
   * @param $consents
   *
   * @return bool
   */
  public static function setStatus($consents) {
    if (!$consents) {
      return self::STATUS_NOTPROVIDED;
    }
    // fixme first approach, looking at first consent (should be for us, not for partner)
    foreach ($consents as $consent) {
      if ($consent->level == self::STATUS_ACCEPTED) {
        return self::STATUS_ACCEPTED;
      }
    }
    // level/status not_provided can be set not only by empty $consents, but also by explicit value equals not_provided
    foreach ($consents as $consent) {
      if ($consent->level == self::STATUS_NOTPROVIDED) {
        return self::STATUS_NOTPROVIDED;
      }
    }

    return self::STATUS_REJECTED;
  }

}
