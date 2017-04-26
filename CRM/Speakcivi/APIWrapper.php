<?php

class CRM_Speakcivi_APIWrapper implements API_Wrapper {

  public function fromApiInput($apiRequest) {
    return $apiRequest;
  }

  public function toApiOutput($apiRequest, $result) {
    if ($apiRequest['entity'] == 'MailingEventUnsubscribe' && $apiRequest['action'] == 'create') {
      $this->processUnsubscribe($apiRequest);
    }
    return $result;
  }

  private function processUnsubscribe($apiRequest) {
    $eq = new CRM_Mailing_Event_BAO_Queue();
    $eq->id = $apiRequest['params']['event_queue_id'];
    if ($eq->find(TRUE)) {
      if ($this->contactHasMoreJoinsThanLeaves($eq->contact_id)) {
        $this->addLeave($eq->contact_id);
      }
    }
  }

  private function contactHasMoreJoinsThanLeaves($contactId) {
    $query = "SELECT IF(a.activity_type_id = 57, 1, 0) test
              FROM civicrm_activity a
                JOIN civicrm_activity_contact ac ON ac.activity_id = a.id AND ac.contact_id = %1
              WHERE a.activity_type_id IN (56, 57)
              ORDER BY a.id DESC
              LIMIT 1";
    $params = array(
      1 => array($contactId, 'Integer'),
    );
    return (boolean)CRM_Core_DAO::singleValueQuery($query, $params);
  }

  private function addLeave($contactId) {
    $data = array(
      0 => array(
        'id' => $contactId,
        'subject' => 'unsubscribe',
        'activity_date_time' => date('YmdHis'),
      )
    );
    CRM_Speakcivi_Cleanup_Leave::createActivitiesInBatch($data);
  }
}
