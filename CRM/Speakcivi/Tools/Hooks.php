<?php

class CRM_Speakcivi_Tools_Hooks {

  static $null = NULL;

  static function setParams(&$params) {
    return CRM_Utils_Hook::singleton()->invoke(1, $params, self::$null, self::$null, self::$null, self::$null, self::$null, 'civicrm_speakciviParams');
  }
}
