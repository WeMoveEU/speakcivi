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
      $contactHasMembersGroup = $this->contactHasMembersGroup($eq->contact_id);
      if (!$contactHasMembersGroup) {
        $campaignId = $this->setCampaignId($eq->id);
        $this->addLeave($eq->contact_id, $campaignId);
      }
      if ($contactHasMembersGroup) {
        $email = new CRM_Core_BAO_Email();
        $email->id = $eq->email_id;
        $email->on_hold = 2;
        $email->hold_date = date('YmdHis');
        $email->save();
      }
    }
  }

  private function contactHasMembersGroup($contactId) {
    $query = "SELECT count(id)
              FROM civicrm_group_contact
              WHERE group_id = 42 AND contact_id = %1 AND status = 'Added'";
    $params = array(
      1 => array($contactId, 'Integer'),
    );
    return (boolean) CRM_Core_DAO::singleValueQuery($query, $params);
  }

  private function setCampaignId($eventQueueId) {
    $campaignId = Civi::cache()->get('speakcivi-campaign-eventqueue-' . $eventQueueId);
    if (!isset($campaignId)) {
      $campaignId = $this->findCampaignId($eventQueueId);
      Civi::cache()->set('speakcivi-campaign-eventqueue-' . $eventQueueId, $campaignId);
    }
    return $campaignId;
  }

  private function findCampaignId($eventQueueId) {
    $query = "SELECT m.campaign_id
              FROM civicrm_mailing_event_queue eq
                JOIN civicrm_mailing_job mj ON mj.id = eq.job_id
                JOIN civicrm_mailing m ON m.id = mj.mailing_id
              WHERE eq.id = %1";
    $queryParams = array(
      1 => array($eventQueueId, 'Integer'),
    );
    return (int) CRM_Core_DAO::singleValueQuery($query, $queryParams);
  }

  private function addLeave($contactId, $campaignId) {
    $data = array(
      'contact_id' => $contactId,
      'campaign_id' => $campaignId,
      'subject' => 'unsubscribe',
      'activity_date_time' => date('YmdHis'),
    );
    CRM_Speakcivi_Logic_Activity::leave($data['contact_id'], $data['subject'], $data['campaign_id'], 0, $data['activity_date_time'], 'Added by SpeakCivi API');
  }

}
