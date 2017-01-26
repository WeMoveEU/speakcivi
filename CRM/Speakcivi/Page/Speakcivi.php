<?php

require_once 'CRM/Core/Page.php';

class CRM_Speakcivi_Page_Speakcivi extends CRM_Core_Page {

  public $optIn = 0;

  public $groupId = 0;

  public $noMemberGroupId = 0;

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

  public $addJoinActivity = false;

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
    $param = json_decode(file_get_contents('php://input'));
    CRM_Speakcivi_Tools_Hooks::setParams($param);
    if (!$param) {
      die ("missing POST PARAM");
    }
    $this->runParam($param);
  }

  public function runParam($param) {
    CRM_Speakcivi_Tools_Hooks::setParams($param);
    $this->setDefaults();
    $this->setCountry($param);

    $notSendConfirmationToThoseCountries = array(
      'FR',
      'GB',
      'IT',
      'UK',
      'ES',
    );
    if (in_array($this->country, $notSendConfirmationToThoseCountries)) {
      $this->optIn = 0;
    }

    $this->campaignObj = new CRM_Speakcivi_Logic_Campaign();
    $this->campaign = $this->campaignObj->getCampaign($param->external_id);
    $this->campaign = $this->campaignObj->setCampaign($param->external_id, $this->campaign, $param);
    if ($this->campaignObj->isValidCampaign($this->campaign)) {
      $this->campaignId = (int)$this->campaign['id'];
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

      case 'petition_no_member':
        $this->petitionNoMember($param);
        break;

      case 'share':
        $this->addActivity($param, 'share');
        break;

      case 'tweet':
        $this->addActivity($param, 'Tweet');
        break;

      case 'speakout':
        $this->addActivity($param, 'Email');
        break;

      case 'donate':
        $this->donate($param);
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
    $this->noMemberGroupId = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'no_member_group_id');
    $this->defaultCampaignTypeId = CRM_Core_OptionGroup::getValue('campaign_type', 'Petitions', 'name', 'String', 'value');
    $this->countryLangMapping = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'country_lang_mapping');
    $this->genderFemaleValue = CRM_Core_OptionGroup::getValue('gender', 'Female', 'name', 'String', 'value');
    $this->genderMaleValue = CRM_Core_OptionGroup::getValue('gender', 'Male', 'name', 'String', 'value');
    $this->genderUnspecifiedValue = CRM_Core_OptionGroup::getValue('gender', 'unspecified', 'name', 'String', 'value');
  }


  /**
   * Setting up country and postal code from address key
   *
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
          $this->country = strtoupper(@$param->cons_hash->addresses[0]->country);
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
    $contact = $this->createContact($param, $this->groupId);

    $optInForActivityStatus = $this->optIn;
    if (!CRM_Speakcivi_Logic_Contact::isContactNeedConfirmation($this->newContact, $contact['id'], $this->groupId, $contact['values'][0]['is_opt_out'])) {
      $this->confirmationBlock = false;
      $optInForActivityStatus = 0;
    }

    $optInMapActivityStatus = array(
      0 => 'Completed',
      1 => 'Scheduled', // default
    );
    if ($this->addJoinActivity && !$this->optIn) {
      $optInMapActivityStatus[0] = 'optin';
    }
    $activityStatus = $optInMapActivityStatus[$optInForActivityStatus];
    $activity = $this->createActivity($param, $contact['id'], 'Petition', $activityStatus);
    CRM_Speakcivi_Logic_Activity::setSourceFields($activity['id'], @$param->source);
    if ($this->newContact) {
      CRM_Speakcivi_Logic_Contact::setContactCreatedDate($contact['id'], $activity['values'][0]['activity_date_time']);
      CRM_Speakcivi_Logic_Contact::setSourceFields($contact['id'], @$param->source);
    }

    $h = $param->cons_hash;
    if ($this->optIn == 1) {
      $this->sendConfirm($h->emails[0]->email, $contact['id'], $activity['id'], $this->campaignId, $this->confirmationBlock, false);
    } else {
      $language = substr($this->locale, 0, 2);
      $pagePost = new CRM_Speakcivi_Page_Post();
      $rlg = $pagePost->setLanguageGroup($contact['id'], $language);
      $pagePost->setLanguageTag($contact['id'], $language);
      if ($this->addJoinActivity) {
        CRM_Speakcivi_Logic_Activity::join($contact['id'], 'optIn:0', $this->campaignId);
      }
      if ($contact['values'][0]['preferred_language'] != $this->locale && $rlg == 1) {
        CRM_Speakcivi_Logic_Contact::set($contact['id'], array('preferred_language' => $this->locale));
      }
      $share_utm_source = 'new_'.str_replace('gb', 'uk', strtolower($this->country)).'_member';
      $this->sendConfirm($h->emails[0]->email, $contact['id'], $activity['id'], $this->campaignId, false, false, $share_utm_source);
    }

  }


  /**
   * Create a petition in Civi: contact and activity but don't add for our members (no groups, no tags)
   *
   * @param $param
   */
  public function petitionNoMember($param) {
    $contact = $this->createContact($param, $this->noMemberGroupId);

    $optInForActivityStatus = $this->optIn;
    if (!CRM_Speakcivi_Logic_Contact::isContactNeedConfirmation($this->newContact, $contact['id'], $this->noMemberGroupId, $contact['values'][0]['is_opt_out'])) {
      $this->confirmationBlock = false;
      $optInForActivityStatus = 0;
    }

    $optInMapActivityStatus = array(
      0 => 'Completed',
      1 => 'Scheduled', // default
    );
    if ($this->addJoinActivity && !$this->optIn) {
      $optInMapActivityStatus[0] = 'optin';
    }
    $activityStatus = $optInMapActivityStatus[$optInForActivityStatus];
    $activity = $this->createActivity($param, $contact['id'], 'Petition', $activityStatus);
    CRM_Speakcivi_Logic_Activity::setSourceFields($activity['id'], @$param->source);
    if ($this->newContact) {
      CRM_Speakcivi_Logic_Contact::setContactCreatedDate($contact['id'], $activity['values'][0]['activity_date_time']);
      CRM_Speakcivi_Logic_Contact::setSourceFields($contact['id'], @$param->source);
    }

    $h = $param->cons_hash;
    if ($this->optIn == 1) {
      $this->sendConfirm($h->emails[0]->email, $contact['id'], $activity['id'], $this->campaignId, $this->confirmationBlock, true);
    } else {
      $share_utm_source = 'new_'.str_replace('gb', 'uk', strtolower($this->country)).'_member';
      $this->sendConfirm($h->emails[0]->email, $contact['id'], $activity['id'], $this->campaignId, false, true, $share_utm_source);
    }
  }


  /**
   * Create a activity
   *
   * @param array $param Params from speakout
   * @param string $type Type name of activity
   * @param string $status Status name of activity
   */
  public function addActivity($param, $type, $status = 'Completed') {
    $contact = $this->createContact($param, $this->groupId);
    $activity = $this->createActivity($param, $contact['id'], $type, $status);
    CRM_Speakcivi_Logic_Activity::setSourceFields($activity['id'], @$param->source);
    CRM_Speakcivi_Logic_Activity::setShareFields($activity['id'], @$param->metadata->tracking_codes);
  }


  /**
   * @param $text
   */
  public function bark($text) {
   $fh = fopen("/tmp/civi.log", "a");
   fwrite($fh, $text . "\n");
   fclose($fh);
  }


  /**
   * Create a transaction for donation
   *
   * @param $param
   *
   * @return bool
   */
  public function donate($param) {
    if ($param->metadata->status == "success") {
      $contact = $this->createContact($param, $this->groupId);
      $contribution = $this->createContribution($param, $contact["id"]);
      if ($this->newContact) {
        CRM_Speakcivi_Logic_Contact::setContactCreatedDate($contact['id'], $contribution['values'][0]['receive_date']);
      }
      return true;
    } else {
      return false;
    }
  }


  /**
   * Create a transaction entity
   *
   * @param $param
   * @param $contactId
   *
   * @return array
   */
  public function createContribution($param, $contactId) {
    $financialTypeId = 1; // How to fetch it by name? No documentation mentions this, so it remains hardcoded, yey!
    $this->bark("Campaign: " . $this->campaignId);
    $params = array(
      'sequential' => 1,
      'source_contact_id' => $contactId,
      'contact_id' => $contactId,
      'contribution_campaign_id' => $this->campaignId,
      'financial_type_id' => $financialTypeId,
      'receive_date' => $param->create_dt,
      'total_amount' => $param->metadata->amount / 100,
      'fee_amount' => $param->metadata->amount_charged / 100,
      'net_amount' => ($param->metadata->amount - $param->metadata->amount_charged) / 100,
      'trxn_id' => $param->metadata->transaction_id,
      'contribution_status' => 'Completed',
      'currency' => $param->metadata->currency,
      'subject' => $param->action_name,
      'location' => $param->action_technical_type,
    );
    return civicrm_api3('Contribution', 'create', $params);
  }


  /**
   * Create or update contact
   *
   * @param array $param
   * @param int $groupId
   *
   * @return array
   */
  public function createContact($param, $groupId) {
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
        'group_id' => $groupId,
        'contact_id' => '$value.id',
        'status' => 'Added',
      ),
      'return' => 'id,email,first_name,last_name',
    );

    $contacIds = CRM_Speakcivi_Logic_Contact::getContactByEmail($h->emails[0]->email);
    if (is_array($contacIds) && count($contacIds) > 0) {
      $contact['id'] = array('IN' => array_keys($contacIds));
      $result = civicrm_api3('Contact', 'get', $contact);
      if ($result['count'] == 1) {
        $contact = $this->prepareParamsContact($param, $contact, $groupId, $result, $result['values'][0]['id']);
      } elseif ($result['count'] > 1) {
        $lastname = $this->cleanLastname($h->lastname);
        $newContact = $contact;
        $newContact['first_name'] = $h->firstname;
        $newContact['last_name'] = $lastname;
        $similarity = $this->glueSimilarity($newContact, $result['values']);
        unset($newContact);
        $contactIdBest = $this->chooseBestContact($similarity);
        $contact = $this->prepareParamsContact($param, $contact, $groupId, $result, $contactIdBest);
      }
    } else {
      $this->newContact = true;
      $contact = $this->prepareParamsContact($param, $contact, $groupId);
    }
    return civicrm_api3('Contact', 'create', $contact);
  }


  /**
   * Get gender id based on lastname. Format: Lastname [?], M -> Male, F -> Femail, others -> Unspecific
   *
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
   *
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
   *
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
   *
   * @param array $param
   * @param array $contact
   * @param int $groupId
   * @param array $result
   * @param int $basedOnContactId
   *
   * @return mixed
   */
  function prepareParamsContact($param, $contact, $groupId, $result = array(), $basedOnContactId = 0) {
    $h = $param->cons_hash;

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
      if ($existingContact['first_name'] == '' && $h->firstname) {
        $contact['first_name'] = $h->firstname;
      }
      if ($existingContact['last_name'] == '' && $h->lastname) {
        $lastname = $this->cleanLastname($h->lastname);
        if ($lastname) {
          $contact['last_name'] = $lastname;
        }
      }
      $contact = $this->prepareParamsAddress($contact, $existingContact);
      if (!$this->optIn && $existingContact[$this->apiGroupContactGet]['count'] == 0) {
        $contact[$this->apiGroupContactCreate] = array(
          'group_id' => $groupId,
          'contact_id' => '$value.id',
          'status' => 'Added',
        );
        $this->addJoinActivity = true;
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
      if (!$this->optIn) {
        $contact[$this->apiGroupContactCreate] = array(
          'group_id' => $groupId,
          'contact_id' => '$value.id',
          'status' => 'Added',
        );
        $this->addJoinActivity = true;
      }
    }
    $contact = $this->removeNullAddress($contact);
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
      $theSame = false;
      foreach ($existingContact[$this->apiAddressGet]['values'] as $k => $v) {
        $adr = $this->getAddressValues($v);
        if (
          array_key_exists('country_id', $adr) && $this->countryId == $adr['country_id'] &&
          array_key_exists('postal_code', $adr) && $this->postalCode == $adr['postal_code']
        ) {
          $contact[$this->apiAddressCreate]['id'] = $v['id'];
          $theSame = true;
          break;
        }
      }
      $postal = false;
      if (!$theSame) {
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
      if (!$theSame && !$postal) {
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
   *
   * @param $contact
   */
  function prepareParamsAddressDefault($contact) {
    $contact[$this->apiAddressCreate]['location_type_id'] = 1;
    $contact[$this->apiAddressCreate]['postal_code'] = $this->postalCode;
    $contact[$this->apiAddressCreate]['country'] = $this->country;
    return $contact;
  }


  /**
   * Remove null params from address
   *
   * @param $contact
   *
   * @return array
   */
  function removeNullAddress($contact) {
    if (array_key_exists('postal_code', $contact[$this->apiAddressCreate]) && $contact[$this->apiAddressCreate]['postal_code'] == '') {
      unset($contact[$this->apiAddressCreate]['postal_code']);
    }
    if (array_key_exists('country', $contact[$this->apiAddressCreate]) && $contact[$this->apiAddressCreate]['country'] == '') {
      unset($contact[$this->apiAddressCreate]['country']);
    }
    if (array_key_exists('id', $contact[$this->apiAddressCreate]) && count($contact[$this->apiAddressCreate]) == 1) {
      unset($contact[$this->apiAddressCreate]['id']);
    }
    if (count($contact[$this->apiAddressCreate]) == 0) {
      unset($contact[$this->apiAddressCreate]);
    }
    return $contact;
  }


  /**
   * Return relevant keys from address
   *
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
   * Calculate similarity between two contacts based on defined keys
   *
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
   * Calculate and glue similarity between new contact and all retrieved from database
   *
   * @param array $newContact
   * @param array $contacts Array from API.Contact.get, key 'values'
   *
   * @return array
   */
  function glueSimilarity($newContact, $contacts) {
    $similarity = array();
    foreach ($contacts as $k => $c) {
      $similarity[$c['id']] = $this->calculateSimilarity($newContact, $c);
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
    $contactIds = array();
    foreach ($similarity as $k => $v) {
      if ($max == $v) {
        $contactIds[$k] = $k;
      }
    }
    return min(array_keys($contactIds));
  }


  /**
   * Create new activity for contact
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
    $activityStatusId = CRM_Core_OptionGroup::getValue('activity_status', $activityStatus, 'name', 'String', 'value');
    $params = array(
      'sequential' => 1,
      'source_contact_id' => $contactId,
      'source_record_id' => $param->external_id,
      'campaign_id' => $this->campaignId,
      'activity_type_id' => $activityTypeId,
      'activity_date_time' => $param->create_dt,
      'subject' => $param->action_name,
      'location' => $param->action_technical_type,
      'status_id' => $activityStatusId,
    );
    if (property_exists($param, 'comment') && $param->comment != '') {
      $params['details'] = trim($param->comment);
    }
    if (property_exists($param, 'metadata')) {
      if (property_exists($param->metadata, 'sign_comment') && $param->metadata->sign_comment != '') {
        $params['details'] = trim($param->metadata->sign_comment);
      }
      if (property_exists($param->metadata, 'mail_to_subject') && property_exists($param->metadata, 'mail_to_body')) {
        $params['details'] = trim($param->metadata->mail_to_subject) . "\n\n" . trim($param->metadata->mail_to_body);
      }
    }
    // fixme move this fix to utils and fix other text fields
    // those unicode chars invoke bugs
    if (key_exists('details', $params)) {
      $params['details'] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $params['details']);
    }
    return CRM_Speakcivi_Logic_Activity::setActivity($params);
  }


  /**
   * Send confirmation mail to contact
   *
   * @param $email
   * @param $contactId
   * @param $activityId
   * @param $campaignId
   * @param $confirmationBlock
   * @param $noMember
   * @param $share_utm_source
   *
   * @return array
   * @throws CiviCRM_API3_Exception
   */
  public function sendConfirm($email, $contactId, $activityId, $campaignId, $confirmationBlock, $noMember, $share_utm_source = '') {
    $params = array(
      'sequential' => 1,
      'toEmail' => $email,
      'contact_id' => $contactId,
      'activity_id' => $activityId,
      'campaign_id' => $campaignId,
      'confirmation_block' => $confirmationBlock,
      'no_member' => $noMember,
    );
    if ($share_utm_source) {
      $params['share_utm_source'] = $share_utm_source;
    }
    return civicrm_api3("Speakcivi", "sendconfirm", $params);
  }
}
