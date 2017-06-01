<?php

class CRM_Speakcivi_Tools_Helper {

  /**
   * Clean out string from unicode special chars.
   * Those unicode chars invoke bugs during insert/update on db table.
   *
   * @param string $string
   *
   * @return mixed
   */
  public static function cleanUnicodeChars($string) {
    $forbiddenRegex = '/['
      . '\x00-\x1F\x80-\xFF'
      . '\x{1F600}-\x{1F64F}'  // Match Emoticons
      . '\x{1F300}-\x{1F5FF}'  // Match Miscellaneous Symbols and Pictographs
      . '\x{1F680}-\x{1F6FF}'  // Match Transport And Map Symbols
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
