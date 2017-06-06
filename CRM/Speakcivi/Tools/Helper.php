<?php

class CRM_Speakcivi_Tools_Helper {

  /**
   * Clean out string from unicode special chars not supported by MySQL 5.7.
   * "No support for supplementary characters (BMP characters only)."
   *
   * @link https://dev.mysql.com/doc/refman/5.7/en/charset-unicode-utf8.html Description of utf8 in MySQL documentation
   * @link http://www.fileformat.info/info/unicode/block/index.htm List of blocks for checking
   *
   * @param string $string
   *
   * @return mixed
   */
  public static function cleanUnicodeChars($string) {
    $forbiddenRegex = '/['
      . '\x{10000}-\x{1F9FF}'  // The second level (U+10000 to U+1FFFF) is the Supplementary Multilingual Plane (Plane 1, SMP)
      . '\x{20000}-\x{2FFFF}'  // The second level (U+20000 to U+2FFFF) is the Supplementary Ideographic Plane (Plane 2, SIP)
      . '\x{2600}-\x{26FF}'  // Match Miscellaneous Symbols
      . '\x{2700}-\x{27BF}'  // Match Dingbats
      . ']/u';
    return preg_replace($forbiddenRegex, '', $string);
  }


  /**
   * Trim all strings from object.
   *
   * @param object $param
   */
  public static function trimVariables(&$param) {
    foreach ($param as $key => $value) {
      if (is_object($value)) {
        self::trimVariables($value);
      } elseif (is_array($value)) {
        foreach($value as $k => $v) {
          self::trimVariables($v);
        }
      } elseif (is_string($value)) {
        $param->$key = self::clean($value, $key);
      }
    }
  }


  /**
   * Clean out string from redundant spaces and trim to valid length.
   *
   * @param string $value
   * @param string $type
   *
   * @return string
   */
  private static function clean($value, $type = '') {
    $value = self::cleanUnicodeChars($value);
    $value = self::cleanSpaces($value);
    switch ($type) {
      case 'phone':
        $value = mb_substr($value, 0, 32);
        break;
      case 'firstname':
      case 'lastname':
      case 'zip':
        $value = mb_substr($value, 0, 64);
        break;
      case 'email':
        $value = mb_substr($value, 0, 254);
        break;
    }
    return $value;
  }


  /**
   * Reduce redundant spaces.
   *
   * @param string $value
   *
   * @return string
   */
  private static function cleanSpaces($value) {
    return preg_replace('/\s+/', ' ', trim($value));
  }
}
