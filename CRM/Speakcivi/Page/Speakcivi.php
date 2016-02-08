<?php

require_once 'CRM/Core/Page.php';

class CRM_Speakcivi_Page_Speakcivi extends CRM_Core_Page {

  public $optIn = 0;

  public $groupId = 0;

  public $defaultCampaignTypeId = 0;

  public $locale = '';

  public $countryLangMapping = array();

  public $country = '';

  public $countryId = 0;

  public $postalCode = '';

  public $campaignObj;

  public $campaign = array();

  public $campaignId = 0;

  public $customFields = array();

  public $newContact = false;

  public $genderMaleValue = 0;

  public $genderFemaleValue = 0;

  public $genderUnspecifiedValue = 0;

  /** @var bool Determine whether confirmation block with links have to be included in content of confirmation email. */
  public $confirmationBlock = true;

  private $apiAddressGet = 'api.Address.get';

  private $apiAddressCreate = 'api.Address.create';

  private $apiGroupContactGet = 'api.GroupContact.get';

  private $apiGroupContactCreate = 'api.GroupContact.create';

  function run() {
	CRM_Core_Error::debug("param", "pe");

    $param = json_decode(file_get_contents('php://input'));

	CRM_Core_Error::debug("param", $param);

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
      $this->optIn = 0;
    }

    $this->campaignObj = new CRM_Speakcivi_Logic_Campaign();
    $this->campaign = $this->campaignObj->getCampaign($param->external_id);
    $this->campaign = $this->campaignObj->setCampaign($param->external_id, $this->campaign);
    if ($this->campaignObj->isValidCampaign($this->campaign)) {
      $this->campaignId = $this->campaign['id'];
      $this->campaignObj->customFields = $this->campaignObj->getCustomFields($this->campaignId);
      $this->locale = $this->campaignObj->getLanguage();
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

      case 'speakout':
        $this->mail($param);
        break;
      default:
    }

  }


  /**
   *  Setting up default values for parameters.
   */
  function setDefaults() {
    $this->optIn = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'opt_in');
    $this->groupId = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'group_id');
    $this->defaultCampaignTypeId = CRM_Core_OptionGroup::getValue('campaign_type', 'Petitions', 'name', 'String', 'value');
    $this->countryLangMapping = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'country_lang_mapping');
    $this->genderFemaleValue = CRM_Core_OptionGroup::getValue('gender', 'Female', 'name', 'String', 'value');
    $this->genderMaleValue = CRM_Core_OptionGroup::getValue('gender', 'Male', 'name', 'String', 'value');
    $this->genderUnspecifiedValue = CRM_Core_OptionGroup::getValue('gender', 'unspecified', 'name', 'String', 'value');
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
          $this->postalCode = substr(trim($matches[2]), 0, 12);
        } else {
          $this->postalCode = substr(trim($zip), 0, 12);
        }
      }
      if ($this->country) {
        $params = array(
          'sequential' => 1,
          'iso_code' => $this->country,
        );
        $result = civicrm_api3('Country', 'get', $params);
        $this->countryId = (int)$result['values'][0]['id'];
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
    if ($this->newContact) {
      $this->setContactCreatedDate($contact['id'], $param->create_dt);
    }

    $optInForActivityStatus = $this->optIn;
    if (!$this->isContactNeedConfirmation($this->newContact, $contact['id'], $contact['values'][0]['is_opt_out'])) {
      $this->confirmationBlock = false;
      $optInForActivityStatus = 0;
    }

    $optInMapActivityStatus = array(
      0 => 'Completed',
      1 => 'Scheduled', // default
    );
    $activityStatus = $optInMapActivityStatus[$optInForActivityStatus];
    $activity = $this->createActivity($param, $contact['id'], 'Petition Signature', $activityStatus);

    if ($this->optIn == 1) {
      $h = $param->cons_hash;
      $this->sendConfirm($h->emails[0]->email, $contact['id'], $activity['id'], $this->campaignId, $this->confirmationBlock);
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
   * Create a representative mail activity
   *
   * @param $param
   */
  public function mail($param) {

    $contact = $this->createContact($param);
    $activity = $this->createActivity($param, $contact['id'], 'Email', 'Completed');

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
      $lastname = $this->cleanLastname($h->lastname);
      $new_contact = $contact;
      $new_contact['first_name'] = $h->firstname;
      $new_contact['last_name'] = $lastname;
      $similarity = $this->glueSimilarity($new_contact, $result['values']);
      unset($new_contact);
      $contactIdBest = $this->chooseBestContact($similarity);
      $contact = $this->prepareParamsContact($param, $contact, $result, $contactIdBest);
    } else {
      $this->newContact = true;
      $contact = $this->prepareParamsContact($param, $contact, $result);
    }

    return civicrm_api3('Contact', 'create', $contact);

  }


  /**
   * Get gender id based on lastname. Format: Lastname [?], M -> Male, F -> Femail, others -> Unspecific
   * @param $lastname
   *
   * @return int
   */
  function getGenderId($lastname) {
    $re = '/.*\[([FM])\]$/';
    if (preg_match($re, $lastname, $matches)) {
      switch ($matches[1]) {
        case 'F':
          return $this->genderFemaleValue;

        case 'M':
          return $this->genderMaleValue;

        default:
          return $this->genderUnspecifiedValue;
      }
    }
    return $this->genderUnspecifiedValue;
  }


  /**
   * Get gender shortcut based on lastname. Format: Lastname [?], M -> Male, F -> Femail, others -> Unspecific
   * @param $lastname
   *
   * @return string
   */
  function getGenderShortcut($lastname) {
    $re = '/.*\[([FM])\]$/';
    if (preg_match($re, $lastname, $matches)) {
      return $matches[1];
    }
    return '';
  }


  /**
   * Clean lastname from gender
   * @param $lastname
   *
   * @return mixed
   */
  function cleanLastname($lastname) {
    $re = "/(.*)(\\[.*\\])$/";
    return trim(preg_replace($re, '${1}', $lastname));
  }


  /**
   * Preparing params for API Contact.create based on retrieved result.
   * @param array $param
   * @param array $contact
   * @param array $result
   * @param int $basedOnContactId
   *
   * @return mixed
   */
  function prepareParamsContact($param, $contact, $result, $basedOnContactId = 0) {
    $h = $param->cons_hash;

    $optInMapGroupStatus = array(
      0 => 'Added',
      1 => 'Pending', //default
    );

    unset($contact['return']);
    unset($contact[$this->apiAddressGet]);
    unset($contact[$this->apiGroupContactGet]);

    $existingContact = array();
    if ($basedOnContactId > 0) {
      foreach ($result['values'] as $id => $res) {
        if ($res['id'] == $basedOnContactId) {
          $existingContact = $res;
          break;
        }
      }
    }

    if (is_array($existingContact) && count($existingContact) > 0) {
      $contact['id'] = $existingContact['id'];
      if ($existingContact['first_name'] == '') {
        $contact['first_name'] = $h->firstname;
      }
      if ($existingContact['last_name'] == '') {
        $contact['last_name'] = $h->lastname;
      }
      $contact = $this->prepareParamsAddress($contact, $existingContact);
      if ($existingContact[$this->apiGroupContactGet]['count'] == 0) {
        $contact[$this->apiGroupContactCreate] = array(
          'group_id' => $this->groupId,
          'contact_id' => '$value.id',
          'status' => $optInMapGroupStatus[$this->optIn],
        );
      }
    } else {
      $genderId = $this->getGenderId($h->lastname);
      $genderShortcut = $this->getGenderShortcut($h->lastname);
      $lastname = $this->cleanLastname($h->lastname);
      $contact['first_name'] = $h->firstname;
      $contact['last_name'] = $lastname;
      $contact['gender_id'] = $genderId;
      $contact['prefix_id'] = CRM_Speakcivi_Tools_Dictionary::getPrefix($genderShortcut);
      $dict = new CRM_Speakcivi_Tools_Dictionary();
      $dict->parseGroupEmailGreeting();
      $emailGreetingId = $dict->getEmailGreetingId($this->locale, $genderShortcut);
      if ($emailGreetingId) {
        $contact['email_greeting_id'] = $emailGreetingId;
      }
      $contact['preferred_language'] = $this->locale;
      $contact['source'] = 'speakout ' . $param->action_type . ' ' . $param->external_id;
      $contact = $this->prepareParamsAddressDefault($contact);
      $contact[$this->apiGroupContactCreate] = array(
        'group_id' => $this->groupId,
        'contact_id' => '$value.id',
        'status' => $optInMapGroupStatus[$this->optIn],
      );
    }

    return $contact;
  }


  /**
   * Preparing params for creating/update a address.
   *
   * @param $contact
   * @param $existingContact
   *
   * @return mixed
   */
  function prepareParamsAddress($contact, $existingContact) {
    if ($existingContact[$this->apiAddressGet]['count'] == 1) {
      // if we have a one address, we update it by new values (?)
      $contact[$this->apiAddressCreate]['id'] = $existingContact[$this->apiAddressGet]['id'];
      $contact[$this->apiAddressCreate]['postal_code'] = $this->postalCode;
      $contact[$this->apiAddressCreate]['country'] = $this->country;
    } elseif ($existingContact[$this->apiAddressGet]['count'] > 1) {
      // from speakout we have only (postal_code) or (postal_code and country)
      $the_same = false;
      foreach ($existingContact[$this->apiAddressGet]['values'] as $k => $v) {
        $adr = $this->getAddressValues($v);
        if (
          array_key_exists('country_id', $adr) && $this->countryId == $adr['country_id'] &&
          array_key_exists('postal_code', $adr) && $this->postalCode == $adr['postal_code']
        ) {
          $contact[$this->apiAddressCreate]['id'] = $v['id'];
          $the_same = true;
          break;
        }
      }
      $postal = false;
      if (!$the_same) {
        foreach ($existingContact[$this->apiAddressGet]['values'] as $k => $v) {
          $adr = $this->getAddressValues($v);
          if (
            !array_key_exists('country_id', $adr) &&
            array_key_exists('postal_code', $adr) && $this->postalCode == $adr['postal_code']
          ) {
            $contact[$this->apiAddressCreate]['id'] = $v['id'];
            $contact[$this->apiAddressCreate]['country'] = $this->country;
            $postal = true;
            break;
          }
        }
      }
      if (!$the_same && !$postal) {
        foreach ($existingContact[$this->apiAddressGet]['values'] as $k => $v) {
          $adr = $this->getAddressValues($v);
          if (
            array_key_exists('country_id', $adr) && $this->countryId == $adr['country_id'] &&
            !array_key_exists('postal_code', $adr)
          ) {
            $contact[$this->apiAddressCreate]['id'] = $v['id'];
            $contact[$this->apiAddressCreate]['postal_code'] = $this->postalCode;
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
    $contact[$this->apiAddressCreate]['postal_code'] = $this->postalCode;
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
    $expectedKeys = array(
      'city' => '',
      'street_address' => '',
      'postal_code' => '',
      'country_id' => '',
    );
    return array_intersect_key($address, $expectedKeys);
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
   * @param $contactId
   * @param string $activityType
   * @param string $activityStatus
   *
   * @return array
   * @throws CiviCRM_API3_Exception
   */
  public function createActivity($param, $contactId, $activityType = 'Petition', $activityStatus = 'Scheduled') {
    $activityTypeId = CRM_Core_OptionGroup::getValue('activity_type', $activityType, 'name', 'String', 'value');
	CRM_Core_Error::debug("activitytypeid", $activityTypeId);

    $activityStatusId = CRM_Core_OptionGroup::getValue('activity_status', $activityStatus, 'name', 'String', 'value');
    $params = array(
      'source_contact_id' => $contactId,
      'source_record_id' => $param->external_id,
      'campaign_id' => $this->campaignId,
      'activity_type_id' => $activityTypeId,
      'activity_date_time' => $param->create_dt,
      'subject' => $param->action_name,
      'location' => $param->action_technical_type,
      'status_id' => $activityStatusId,
    );
    if (property_exists($param, 'metadata')) {
            if (property_exists($param->metadata, 'sign_comment') && $param->metadata->comment != '') {
                $params['details'] = trim($param->metadata->comment);
            }

            if (property_exists($param->metadata, 'mail_to_subject') 
                && property_exists($param->metadata, 'mail_to_body')) {
                $params['details'] = trim($param->metadata->mail_to_subject) . "\n\n" . trim($param->metadata->mail_to_body);
            }
        }
    return civicrm_api3('Activity', 'create', $params);
  }


  /**
   * Send confirmation mail to contact.
   *
   * @param $email
   * @param $contactId
   * @param $activityId
   * @param $campaignId
   * @param $confirmationBlock
   *
   * @return array
   * @throws CiviCRM_API3_Exception
   */
  public function sendConfirm($email, $contactId, $activityId, $campaignId, $confirmationBlock) {
    $params = array(
      'sequential' => 1,
      'toEmail' => $email,
      'contact_id' => $contactId,
      'activity_id' => $activityId,
      'campaign_id' => $campaignId,
      'confirmation_block' => $confirmationBlock,
    );
    return civicrm_api3("Speakcivi", "sendconfirm", $params);
  }


  /**
   * Check If contact need send email confirmation.
   *
   * @param $newContact
   * @param $contactId
   * @param $isOptOut
   *
   * @return bool
   *
   */
  public function isContactNeedConfirmation($newContact, $contactId, $isOptOut) {
    if ($newContact || $isOptOut) {
      return true;
    } else {
      $params = array(
        'sequential' => 1,
        'contact_id' => $contactId,
        'group_id' => $this->groupId,
      );
      $result = civicrm_api3('GroupContact', 'get', $params);
      if ($result['count'] == 0) {
        return true;
      }
    }
    return false;
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

    if (!($hours == "Z")) {
    $dt->modify("{$hours} hour {$sign}{$mins} minutes");
    // todo temporary solution https://github.com/WeMoveEU/speakcivi/issues/48
    $dt->modify("+1 hour");
    }

    $query = "UPDATE civicrm_contact SET created_date = %2 WHERE id = %1";
    $params = array(
      1 => array($contactId, 'Integer'),
      2 => array($dt->format("Y-m-d H:i:s"), 'String'),
    );
    CRM_Core_DAO::executeQuery($query, $params);
  }
}
