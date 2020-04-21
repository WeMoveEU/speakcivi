<?php

class CRM_Speakcivi_Logic_Language {

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

}
