<?php

require_once 'CRM/Core/Page.php';

class CRM_Speakcivi_Page_Speakcivi extends CRM_Core_Page {

  public $opt_in = 0;

  public $groupId = 0;

  public $default_campaign_type_id = 0;

  public $defaultTemplateId = 0;

  public $defaultLanguage = '';

  public $fieldTemplateId = '';

  public $fieldLanguage = '';

  public $fieldSenderMail = '';

  public $from = '';

  public $country_lang_mapping = array();

  public $country = '';

  public $country_id = 0;

  public $postal_code = '';

  public $campaign = array();

  public $campaignId = 0;

  public $customFields = array();

  public $new_contact = false;

  private $apiAddressGet = 'api.Address.get';

  private $apiAddressCreate = 'api.Address.create';

  private $apiGroupContactGet = 'api.GroupContact.get';

  private $apiGroupContactCreate = 'api.GroupContact.create';


  function run() {

    $param = json_decode(file_get_contents('php://input'));

    if (!$param) {
      die ("missing POST PARAM");
    }

    $this->setDefaults();
    $this->setCountry($param);

    $not_send_confirmation_to_those_countries = array(
      'UK',
      'GB',
    );
    if (in_array($this->country, $not_send_confirmation_to_those_countries)) {
      $this->opt_in = 0;
    }
    CRM_Core_Error::debug_var('$param_______RUN_PARAM', $param, false, true);

    $this->campaign = $this->getCampaign($param->external_id);
    $this->campaign = $this->setCampaign($param->external_id, $this->campaign);
    if ($this->isValidCampaign($this->campaign)) {
      $this->campaignId = $this->campaign['id'];
    } else {
      header('HTTP/1.1 503 Men at work');
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
        CRM_Core_Error::debug_var('Speakcivi API, Unsupported Action Type', $param->action_type, false, true);
    }

  }


  /**
   *  Setting up default values for parameters.
   */
  function setDefaults() {
    $this->opt_in = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'opt_in');
    $this->groupId = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'group_id');
    $this->default_campaign_type_id = CRM_Core_OptionGroup::getValue('campaign_type', 'Petitions', 'name', 'String', 'value');
    $this->defaultTemplateId = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'default_template_id');
    $this->defaultLanguage = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'default_language');
    $this->fieldTemplateId = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_template_id');
    $this->fieldLanguage = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_language');
    $this->fieldSenderMail = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_sender_mail');
    $this->from = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'from');
    $this->country_lang_mapping = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'country_lang_mapping');
  }


  /**
   * Setting up country and postal code from address key
   * @param $param
   */
  function setCountry($param) {
    if (property_exists($param, 'cons_hash')) {
      $zip = @$param->cons_hash->addresses[0]->zip;
      if ($zip != '') {
        $re = "/\\[([a-zA-Z]{2})\\](.*)/";
        if (preg_match($re, $zip, $matches)) {
          $this->country = strtoupper($matches[1]);
          $this->postal_code = substr(trim($matches[2]), 0, 12);
        } else {
          $this->postal_code = substr(trim($zip), 0, 12);
        }
      }
      if ($this->country) {
        $params = array(
          'sequential' => 1,
          'iso_code' => $this->country,
        );
        $result = civicrm_api3('Country', 'get', $params);
        $this->country_id = (int)$result['values'][0]['id'];
      }
    }
  }


  /**
   * Create a petition in Civi: contact and activity
   *
   * @param $param
   */
  public function petition($param) {

    $contact = $this->createContact($param);
    if ($this->new_contact) {
      $this->setContactCreatedDate($contact['id'], $param->create_dt);
    }

    $opt_in_map_activity_status = array(
      0 => 'Completed',
      1 => 'Scheduled', // default
    );
    $activity_status = $opt_in_map_activity_status[$this->opt_in];
    if (
      (property_exists($param, 'boolean_collection') && $param->boolean_collection == false) ||
      (!property_exists($param, 'boolean_collection'))
    ) {
      $activity_status = 'optout';
    }
    $activity = $this->createActivity($param, $contact['id'], 'Petition', $activity_status);

    if ($this->opt_in == 1) {
      $h = $param->cons_hash;
      $this->customFields = $this->getCustomFields($this->campaignId);
      $this->sendConfirm($contact, $h->emails[0]->email, $activity['id']);
    }

  }


  /**
   * Create a sharing activity
   *
   * @param $param
   */
  public function share($param) {

    $contact = $this->createContact($param);
    $activity = $this->createActivity($param, $contact['id'], 'share', 'Completed');

  }


  /**
   * Create or update contact
   *
   * @param $param
   *
   * @return array
   * @throws CiviCRM_API3_Exception
   */
  public function createContact($param) {
    $h = $param->cons_hash;

    $contact = array(
      'sequential' => 1,
      'contact_type' => 'Individual',
      'email' => $h->emails[0]->email,
      $this->apiAddressGet => array(
        'id' => '$value.address_id',
        'contact_id' => '$value.id',
      ),
      $this->apiGroupContactGet => array(
        'group_id' => $this->groupId,
        'contact_id' => '$value.id',
        'status' => 'Added',
      ),
      'return' => 'id,email,first_name,last_name',
    );
    $result = civicrm_api3('Contact', 'get', $contact);

    if ($result['count'] == 1) {
      $contact = $this->prepareParamsContact($param, $contact, $result, $result['values'][0]['id']);
    } elseif ($result['count'] > 1) {
      $new_contact = $contact;
      $new_contact['first_name'] = $h->firstname;
      $new_contact['last_name'] = $h->lastname;
      $similarity = $this->glueSimilarity($new_contact, $result['values']);
      unset($new_contact);
      $contact_id_best = $this->chooseBestContact($similarity);
      $contact = $this->prepareParamsContact($param, $contact, $result, $contact_id_best);
    } else {
      $this->new_contact = true;
      $contact = $this->prepareParamsContact($param, $contact, $result);
    }

    CRM_Core_Error::debug_var('$createContact_PARAMS', $contact, false, true);
    return civicrm_api3('Contact', 'create', $contact);

  }


  /**
   * Preparing params for API Contact.create based on retrieved result.
   * @param array $param
   * @param array $contact
   * @param array $result
   * @param int $based_on_contact_id
   *
   * @return mixed
   */
  function prepareParamsContact($param, $contact, $result, $based_on_contact_id = 0) {
    $h = $param->cons_hash;

    $opt_in_map_group_status = array(
      0 => 'Added',
      1 => 'Pending', //default
    );

    unset($contact['return']);
    unset($contact[$this->apiAddressGet]);
    unset($contact[$this->apiGroupContactGet]);

    $existing_contact = array();
    if ($based_on_contact_id > 0) {
      foreach ($result['values'] as $id => $res) {
        if ($res['id'] == $based_on_contact_id) {
          $existing_contact = $res;
          break;
        }
      }
    }

    if (is_array($existing_contact) && count($existing_contact) > 0) {
      $contact['id'] = $existing_contact['id'];
      if ($existing_contact['first_name'] == '') {
        $contact['first_name'] = $h->firstname;
      }
      if ($existing_contact['last_name'] == '') {
        $contact['last_name'] = $h->lastname;
      }
      $contact = $this->prepareParamsAddress($contact, $existing_contact);
      if ($existing_contact[$this->apiGroupContactGet]['count'] == 0) {
        $contact[$this->apiGroupContactCreate] = array(
          'group_id' => $this->groupId,
          'contact_id' => '$value.id',
          'status' => $opt_in_map_group_status[$this->opt_in],
        );
      }
    } else {
      $this->customFields = $this->getCustomFields($this->campaignId);
      $contact['first_name'] = $h->firstname;
      $contact['last_name'] = $h->lastname;
      $contact['preferred_language'] = $this->getLanguage();
      $contact['source'] = 'speakout ' . $param->action_type . ' ' . $param->external_id;
      if (
        (property_exists($param, 'boolean_collection') && $param->boolean_collection == false) ||
        (!property_exists($param, 'boolean_collection'))
      ) {
        $contact['is_opt_out'] = 1;
      }
      $contact = $this->prepareParamsAddressDefault($contact);
      $contact[$this->apiGroupContactCreate] = array(
        'group_id' => $this->groupId,
        'contact_id' => '$value.id',
        'status' => $opt_in_map_group_status[$this->opt_in],
      );
    }

    return $contact;
  }


  /**
   * Preparing params for creating/update a address.
   *
   * @param $contact
   * @param $existing_contact
   *
   * @return mixed
   */
  function prepareParamsAddress($contact, $existing_contact) {
    if ($existing_contact[$this->apiAddressGet]['count'] == 1) {
      // if we have a one address, we update it by new values (?)
      $contact[$this->apiAddressCreate]['id'] = $existing_contact[$this->apiAddressGet]['id'];
      $contact[$this->apiAddressCreate]['postal_code'] = $this->postal_code;
      $contact[$this->apiAddressCreate]['country'] = $this->country;
    } elseif ($existing_contact[$this->apiAddressGet]['count'] > 1) {
      // from speakout we have only (postal_code) or (postal_code and country)
      $the_same = false;
      foreach ($existing_contact[$this->apiAddressGet]['values'] as $k => $v) {
        $adr = $this->getAddressValues($v);
        if (
          array_key_exists('country_id', $adr) && $this->country_id == $adr['country_id'] &&
          array_key_exists('postal_code', $adr) && $this->postal_code == $adr['postal_code']
        ) {
          $contact[$this->apiAddressCreate]['id'] = $v['id'];
          $the_same = true;
          break;
        }
      }
      $postal = false;
      if (!$the_same) {
        foreach ($existing_contact[$this->apiAddressGet]['values'] as $k => $v) {
          $adr = $this->getAddressValues($v);
          if (
            !array_key_exists('country_id', $adr) &&
            array_key_exists('postal_code', $adr) && $this->postal_code == $adr['postal_code']
          ) {
            $contact[$this->apiAddressCreate]['id'] = $v['id'];
            $contact[$this->apiAddressCreate]['country'] = $this->country;
            $postal = true;
            break;
          }
        }
      }
      if (!$the_same && !$postal) {
        foreach ($existing_contact[$this->apiAddressGet]['values'] as $k => $v) {
          $adr = $this->getAddressValues($v);
          if (
            array_key_exists('country_id', $adr) && $this->country_id == $adr['country_id'] &&
            !array_key_exists('postal_code', $adr)
          ) {
            $contact[$this->apiAddressCreate]['id'] = $v['id'];
            $contact[$this->apiAddressCreate]['postal_code'] = $this->postal_code;
            break;
          }
        }
      }
      if (!array_key_exists($this->apiAddressCreate, $contact) || !array_key_exists('id', $contact[$this->apiAddressCreate])) {
        unset($contact[$this->apiAddressCreate]);
        $contact = $this->prepareParamsAddressDefault($contact);
      }
    } else {
      // we have no address, creating new one
      $contact = $this->prepareParamsAddressDefault($contact);
    }
    return $contact;
  }


  /**
   * Prepare default address
   * @param $contact
   */
  function prepareParamsAddressDefault($contact) {
    $contact[$this->apiAddressCreate]['location_type_id'] = 1;
    $contact[$this->apiAddressCreate]['postal_code'] = $this->postal_code;
    $contact[$this->apiAddressCreate]['country'] = $this->country;
    return $contact;
  }


  /**
   * Return relevant keys from address
   * @param $address
   *
   * @return array
   */
  function getAddressValues($address) {
    $expected_keys = array(
      'city' => '',
      'street_address' => '',
      'postal_code' => '',
      'country_id' => '',
    );
    return array_intersect_key($address, $expected_keys);
  }


  /**
   * Calculate similarity between two contacts based on defined keys.
   * @param $contact1
   * @param $contact2
   *
   * @return int
   */
  function calculateSimilarity($contact1, $contact2) {
    $keys = array(
      'first_name',
      'last_name',
      'email',
    );
    $points = 0;
    foreach ($keys as $key) {
      if ($contact1[$key] == $contact2[$key]) {
        $points++;
      }
    }
    return $points;
  }


  /**
   * Calculate and glue similarity between new contact and all retrieved from database.
   *
   * @param array $new_contact
   * @param array $contacts Array from API.Contact.get, key 'values'
   *
   * @return array
   */
  function glueSimilarity($new_contact, $contacts) {
    $similarity = array();
    foreach ($contacts as $k => $c) {
      $similarity[$c['id']] = $this->calculateSimilarity($new_contact, $c);
    }
    return $similarity;
  }


  /**
   * Choose the best contact based on similarity. If similarity is the same, choose the oldest one.
   *
   * @param $similarity
   *
   * @return mixed
   */
  function chooseBestContact($similarity) {
    $max = max($similarity);
    $contact_ids = array();
    foreach ($similarity as $k => $v) {
      if ($max == $v) {
        $contact_ids[$k] = $k;
      }
    }
    return min(array_keys($contact_ids));
  }


  /**
   * Create new activity for contact.
   *
   * @param $param
   * @param $contact_id
   * @param string $activity_type
   * @param string $activity_status
   *
   * @return array
   * @throws CiviCRM_API3_Exception
   */
  public function createActivity($param, $contact_id, $activity_type = 'Petition', $activity_status = 'Scheduled') {
    $activity_type_id = CRM_Core_OptionGroup::getValue('activity_type', $activity_type, 'name', 'String', 'value');
    $activity_status_id = CRM_Core_OptionGroup::getValue('activity_status', $activity_status, 'name', 'String', 'value');
    $params = array(
      'source_contact_id' => $contact_id,
      'source_record_id' => $param->external_id,
      'campaign_id' => $this->campaignId,
      'activity_type_id' => $activity_type_id,
      'activity_date_time' => $param->create_dt,
      'subject' => $param->action_name,
      'location' => $param->action_technical_type,
      'status_id' => $activity_status_id,
    );
    if (property_exists($param, 'comment') && $param->comment != '') {
      $params['details'] = $param->comment;
    }
    CRM_Core_Error::debug_var('$CreateActivity_PARAMS', $params, false, true);
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
  public function getCampaign($external_identifier) {
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
   * Setting up new campaign in CiviCRM if this is necessary.
   *
   * @param $external_id
   * @param $campaign
   *
   * @return array
   * @throws CiviCRM_API3_Exception
   */
  public function setCampaign($external_id, $campaign) {
    if (!$this->isValidCampaign($campaign)) {
      if ($external_id > 0) {
        $ext_campaign = (object)json_decode(@file_get_contents("https://act.wemove.eu/campaigns/{$external_id}.json"));
        if (is_object($ext_campaign) &&
          property_exists($ext_campaign, 'name') && $ext_campaign->name != '' &&
          property_exists($ext_campaign, 'id') && $ext_campaign->id > 0
        ) {
          $ext_campaign->msg_template_id = $this->defaultTemplateId;
          $ext_campaign->preferred_language = $this->determineLanguage($ext_campaign->name);
          $params = array(
            'sequential' => 1,
            'title' => $ext_campaign->name,
            'external_identifier' => $ext_campaign->id,
            'campaign_type_id' => $this->default_campaign_type_id,
            'start_date' => date('Y-m-d H:i:s'),
            $this->fieldTemplateId => $ext_campaign->msg_template_id,
            $this->fieldLanguage => $ext_campaign->preferred_language,
            $this->fieldSenderMail => $this->from,
          );
          $result = civicrm_api3('Campaign', 'create', $params);
          if ($result['count'] == 1) {
            return $result['values'][0];
          }
        }
      }
      return array();
    } else {
      return $campaign;
    }
  }


  /**
   * Determine language based on campaign name which have to include country on the end, ex. *_EN.
   *
   * @param $campaign_name
   *
   * @return string
   */
  function determineLanguage($campaign_name) {
    $re = "/(.*)[_ ]([a-zA-Z]{2})$/";
    if (preg_match($re, $campaign_name, $matches)) {
      $country = strtoupper($matches[2]);
      if (array_key_exists($country, $this->country_lang_mapping)) {
        return $this->country_lang_mapping[$country];
      }
    }
    return $this->defaultLanguage;
  }


  /**
   * Determine whether $campaign table has a valid structure.
   *
   * @param $campaign
   *
   * @return bool
   */
  public function isValidCampaign($campaign) {
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
   * Send confirmation mail to contact.
   *
   * @param $contact_result
   * @param $email
   * @param $activity_id
   *
   * @return array
   * @throws CiviCRM_API3_Exception
   */
  public function sendConfirm($contact_result, $email, $activity_id) {
    $params = array(
      'sequential' => 1,
      'messageTemplateID' => $this->getTemplateId(),
      'toEmail' => $email,
      'contact_id' => $contact_result['id'],
      'activity_id' => $activity_id,
      'campaign_id' => $this->campaignId,
      'from' => $this->getSenderMail(),
    );
    CRM_Core_Error::debug_var('$SpeakciviSendConfirm_PARAMS', $params, false, true);
    return civicrm_api3("Speakcivi", "sendconfirm", $params);
  }


  /**
   * Get custom fields for campaign Id.
   * Warning! Switch on permission "CiviCRM: access all custom data" for "ANONYMOUS USER"
   * @param $campaignId
   *
   * @return array
   * @throws CiviCRM_API3_Exception
   */
  public function getCustomFields($campaignId) {
    $params = array(
      'sequential' => 1,
      'return' => "{$this->fieldTemplateId},{$this->fieldLanguage},{$this->fieldSenderMail}",
      'id' => $campaignId,
    );
    $result = civicrm_api3('Campaign', 'get', $params);
    if ($result['count'] == 1) {
      return $result['values'][0];
    } else {
      return array();
    }
  }


  /**
   * Get message template id from $customFields array generated by getCustomFields() method
   *
   * @return int
   */
  public function getTemplateId() {
    if (is_array($this->customFields) && array_key_exists($this->fieldTemplateId, $this->customFields)) {
      return (int)$this->customFields[$this->fieldTemplateId];
    }
    return 0;
  }


  /**
   * Get language from $customFields array generated by getCustomFields() method
   *
   * @return string
   */
  public function getLanguage() {
    if (is_array($this->customFields) && array_key_exists($this->fieldLanguage, $this->customFields)) {
      return $this->customFields[$this->fieldLanguage];
    }
    return '';
  }


  /**
   * Get language from $customFields array generated by getCustomFields() method
   *
   * @return int
   */
  public function getSenderMail() {
    if (is_array($this->customFields) && array_key_exists($this->fieldSenderMail, $this->customFields)) {
      return $this->customFields[$this->fieldSenderMail];
    }
    return '';
  }


  /**
   * Set up own created date. Column created_date is kind of timestamp and therefore It can't be set up during creating new contact.
   *
   * @param $contactId
   * @param $createdDate
   *
   * @return bool
   *
   */
  public function setContactCreatedDate($contactId, $createdDate) {
    $format = 'Y-m-d\TH:i:s.uP';
    $dt = DateTime::createFromFormat($format, $createdDate);
    $time = explode(':', $dt->getTimezone()->getName());
    $hours = $time[0];
    $mins = $time[1];
    $sign = substr($dt->getTimezone()->getName(), 0, 1);
    $dt->modify("{$hours} hour {$sign}{$mins} minutes");

    $query = "UPDATE civicrm_contact SET created_date = %2 WHERE id = %1";
    $params = array(
      1 => array($contactId, 'Integer'),
      2 => array($dt->format("Y-m-d H:i:s"), 'String'),
    );
    CRM_Core_DAO::executeQuery($query, $params);
  }
}
