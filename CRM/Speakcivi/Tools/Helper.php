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
}
