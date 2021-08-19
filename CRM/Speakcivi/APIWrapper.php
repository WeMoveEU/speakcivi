<?php

class CRM_Speakcivi_APIWrapper implements API_Wrapper {

  public function fromApiInput($apiRequest) {
    return $apiRequest;
  }

  /**
   * @param array $apiRequest
   * @param array $result
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public function toApiOutput($apiRequest, $result) {
    if ($apiRequest['entity'] == 'MailingEventUnsubscribe' && $apiRequest['action'] == 'create') {
      $this->processUnsubscribe($apiRequest);
    }
    return $result;
  }

  /**
   * @param $apiRequest
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function processUnsubscribe($apiRequest) {
    $eq = new CRM_Mailing_Event_BAO_Queue();
    $eq->id = $apiRequest['params']['event_queue_id'];
    if ($eq->find(TRUE)) {
      $params = array(
        'sequential' => 1,
        'id' => $eq->email_id,
        'on_hold' => 2,
        'hold_date' => date('YmdHis'),
      );
      civicrm_api3('Email', 'create', $params);
      $params = array(
        'sequential' => 1,
        'id' => $eq->contact_id,
        'is_opt_out' => 1,
      );
      civicrm_api3('Contact', 'create', $params);
    }
  }

}
