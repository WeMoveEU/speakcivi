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
    return preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $string);
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
        $param->$key = trim($value);
      }
    }
  }
}
