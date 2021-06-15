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

  /**
   * Choose footer for reminder based on Language.
   * Language in short format like EN, DE, IT...
   * English version by default.
   *
   * @param string $shortLanguage
   *
   * @return int
   */
  public static function chooseFooter(string $shortLanguage = 'EN'): int {
    $footers = self::footers();

    return CRM_Utils_Array::value($shortLanguage, $footers);
  }

  /**
   * Grab footers used for reminders and iterate it by language.
   * Name of footer should be /Footer [A-Z]{2}$/
   * @return array
   */
  private static function footers(): array {
    $key = __METHOD__;
    $cache = Civi::cache('long')->get($key);
    if (!isset($cache)) {
      $query = "SELECT id, substr(name, -2) language
                FROM civicrm_mailing_component
                WHERE component_type = 'Footer' AND name LIKE 'Footer __'
                ORDER BY id";
      $dao = CRM_Core_DAO::executeQuery($query);
      $ids = [];
      while ($dao->fetch()) {
        $ids[$dao->language] = (int) $dao->id;
      }
      Civi::cache('long')->set($key, $ids);
      return $ids;
    }
    return $cache;

  }

}
