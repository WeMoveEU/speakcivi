<?php

class CRM_Speakcivi_Logic_Contact {

  /**
   * Get email
   * 
   * @param $contactId
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function getEmail($contactId) {
    $result = civicrm_api3('Contact', 'get', array(
      'sequential' => 1,
      'return' => "email",
      'id' => $contactId,
    ));
    return $result['values'][0]['email'];
  }
}
