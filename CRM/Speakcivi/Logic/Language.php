<?php

class CRM_Speakcivi_Logic_Language {

  /** @var int English language Members UK */
  const ENGLISH_UK_GROUP_ID = 982;

  /** @var int English language Members INT */
  const ENGLISH_INT_GROUP_ID = 983;

  /**
   * Check if language has valid value.
   *
   * @param string $language
   *
   * @return bool
   */
  public static function isValid($language) {
    $languages = CRM_Core_PseudoConstant::get('CRM_Contact_BAO_Contact', 'preferred_language');

    return ($language && array_key_exists($language, $languages));
  }

  /**
   * Check if language is one of english languages.
   *
   * @param string $language
   *
   * @return bool
   */
  public static function isEnglish($language) {
    return in_array($language, ['en_GB', 'en_US', 'en_UK']);
  }

}
