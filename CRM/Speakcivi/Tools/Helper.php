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
    $regexEmoticons = '/[\x00-\x1F\x80-\xFF]/';
    $clean_text = preg_replace($regexEmoticons, '', $string);

    // Match Emoticons
    $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
    $clean_text = preg_replace($regexEmoticons, '', $clean_text);

    // Match Miscellaneous Symbols and Pictographs
    $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
    $clean_text = preg_replace($regexSymbols, '', $clean_text);

    // Match Transport And Map Symbols
    $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
    $clean_text = preg_replace($regexTransport, '', $clean_text);

    // Match Miscellaneous Symbols
    $regexMisc = '/[\x{2600}-\x{26FF}]/u';
    $clean_text = preg_replace($regexMisc, '', $clean_text);

    // Match Dingbats
    $regexDingbats = '/[\x{2700}-\x{27BF}]/u';
    $clean_text = preg_replace($regexDingbats, '', $clean_text);

    return $clean_text;
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
