<?php

require_once 'CRM/Core/Page.php';

class CRM_Bsd_Page_BSD extends CRM_Core_Page {

  // TODO Lookup
  private $groupId = 42;

  private $campaign = array();

  private $campaignId = 0;

  function run() {

    $param = json_decode(file_get_contents('php://input'));

    header('HTTP/1.1 503 Men at work');

    if (!$param) {
      die ("missing POST PARAM");
    }

    $this->campaign = $this->getCampaign($param->external_id);
    if ($this->isValidCampaign($this->campaign)) {
      $this->campaignId = $this->campaign['id'];
    } else {
      return;
    }
    
    switch ($param->action_type) {
      case 'petition':
        $this->petition($param);
        break;
      
      case 'share':
        $this->share($param);
        break;
      
      default:
        CRM_Core_Error::debug_var('BSD API, Unsupported Action Type', $param->action_type, false, true);
    }

  }


  public function petition($param) {

    $contact = $this->createContact($param);
    $activity = $this->createActivity($param, $contact['id'], 'Petition');

    if ($this->checkIfConfirm($param->external_id)) {
      $h = $param->cons_hash;
      $this->sendConfirm($param, $contact, $h->emails[0]->email);
    }

    CRM_Core_Error::debug_var("BSD PETITION", $param + $activity, false, true);

  }


  public function share($param) {

    $contact = $this->createContact($param);
    $activity = $this->createActivity($param, $contact['id'], 'share');
    CRM_Core_Error::debug_var("BSD SHARE", $param + $activity, false, true);

  }


  /**
   * Create or update contact
   *
   * @param $param
   *
   * @return array
   * @throws CiviCRM_API3_Exception
   */
  private function createContact($param) {
    $h = $param->cons_hash;

    $contact = array(
      'sequential' => 1,
      'contact_type' => 'Individual',
      'first_name' => $h->firstname,
      'last_name' => $h->lastname,
      'email' => $h->emails[0]->email,
    );

    $result = civicrm_api3('Contact', 'get', $contact);
    if ($result['count'] == 1) {
      $contact['id'] = $result['values'][0]['id'];
    } else {
      $contact['source'] = 'speakout ' . $param->action_type . ' ' . $param->external_id;
    }

    $apiAddress = 'api.Address.create';
    $apiGroupContact = 'api.GroupContact.create';

    $contact[$apiAddress] = array(
      'postal_code' => $h->addresses[0]->zip,
      'is_primary' => 1,
      'location_type_id' => 1,
    );
    $contact[$apiGroupContact] = array(
      'group_id' => $this->groupId,
      'contact_id' => '$value.id',
      'status' => 'Pending',
    );

    return civicrm_api3('Contact', 'create', $contact);

  }


  /**
   * Create new activity for contact.
   *
   * @param $param
   * @param $contact_id
   * @param string $activity_type
   *
   * @return array
   * @throws CiviCRM_API3_Exception
   */
  private function createActivity($param, $contact_id, $activity_type = 'Petition') {
    $activity_type_id = CRM_Core_OptionGroup::getValue('activity_type', $activity_type, 'name', 'String', 'value');
    $params = array(
      'source_contact_id' => $contact_id,
      'source_record_id' => $this->campaignId,
      'campaign' => $this->campaignId,
      'activity_type_id' => $activity_type_id,
      'activity_date_time' => $param->create_dt,
      'subject' => $param->action_name,
      'location' => $param->action_technical_type,
    );
    CRM_Core_Error::debug_var('$paramsCreateActivity', $params, false, true);
    return civicrm_api3('Activity', 'create', $params);
  }


  /**
   * Get campaign by external identifier.
   *
   * @param $external_identifier
   *
   * @return array
   * @throws CiviCRM_API3_Exception
   */
  private function getCampaign($external_identifier) {
    if ($external_identifier > 0) {
      $params = array(
        'sequential' => 1,
        'external_identifier' => (int)$external_identifier,
      );
      $result = civicrm_api3('Campaign', 'get', $params);
      if ($result['count'] == 1) {
        return $result['values'][0];
      }
    }
    return array();
  }
  

  /**
   * Determine whether $campaign table has a valid structure.
   *
   * @param $campaign
   *
   * @return bool
   */
  private function isValidCampaign($campaign) {
    if (
      is_array($campaign) &&
      array_key_exists('id', $campaign) &&
      $campaign['id'] > 0
    ) {
      return true;
    }
    return false;
  }


  /**
   * Check whether this external campaing (SpeakOut ID Campaign) is marked as unsupported (ex. testing campaign).
   *
   * @param $external_id
   *
   * @return bool
   */
  private function checkIfConfirm($external_id) {
    $notconfirm_external_id = array(
      9,
    );
    return !in_array($external_id, $notconfirm_external_id);
  }


  /**
   * Send confirmation mail to contact.
   *
   * @param $param
   * @param $contact_result
   * @param $email
   *
   * @return array
   * @throws CiviCRM_API3_Exception
   */
  private function sendConfirm($param, $contact_result, $email) {
    // todo ???
    if (!$contact_result['is_error']) {
      $tplid = 69;
    }
    // todo ???
    if ($param->external_id == 8) {
      $tplid = 70;
    }
    $params = array(
      'sequential' => 1,
      'messageTemplateID' => $tplid, // todo retrieve template id from customField (how to do it?)
      'toEmail' => $email,
      'contact_id' => $contact_result['id']
    );
    CRM_Core_Error::debug_var('$paramsSpeakoutSendConfirm', $params, false, true);
    return civicrm_api3("Speakout", "sendconfirm", $params);
  }

}
