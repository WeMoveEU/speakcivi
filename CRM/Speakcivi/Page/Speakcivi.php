<?php

require_once 'CRM/Core/Page.php';

class CRM_Speakcivi_Page_Speakcivi extends CRM_Core_Page {

  public $optIn = 0;

  public $groupId = 0;

  public $noMemberGroupId = 0;

  public $noMemberCampaignType = 0;

  public $thresholdVeryOldActivity = 0;

  public $useAsCurrentActivity = true;

  public $defaultCampaignTypeId = 0;

  public $distributedCampaignTypeId = 6;

  public $locale = '';

  public $countryLangMapping = array();

  public $countryIsoCode = '';

  public $countryId = 0;

  public $postalCode = '';

  public $campaignObj;

  public $campaignId = 0;

  public $newContact = false;

  public $addJoinActivity = false;

  public $genderMaleValue = 0;

  public $genderFemaleValue = 0;

  public $genderUnspecifiedValue = 0;

  /** @var bool Determine whether confirmation block with links have to be included in content of confirmation email. */
  public $confirmationBlock = true;

  private $consents;

  private $consentStatus;

  private $isAnonymous = false;

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
    $result = 0;
    CRM_Core_Transaction::create(TRUE)->run(function(CRM_Core_Transaction $tx) use ($param, &$result) {
      CRM_Speakcivi_Tools_Hooks::setParams($param);
      CRM_Speakcivi_Tools_Helper::trimVariables($param);
      $this->setDefaults();
      $this->setCountry($param);
      $this->setVeryOldActivity($param);
      $this->consents = CRM_Speakcivi_Logic_Consent::prepareFields($param);
      $this->consentStatus = CRM_Speakcivi_Logic_Consent::setStatus($this->consents);

      $this->campaignObj = new CRM_Speakcivi_Logic_Campaign();
      $this->campaignObj->campaign = CRM_Speakcivi_Logic_Cache_Campaign::getCampaignByExternalId($param);
      if ($this->campaignObj->isValidCampaign($this->campaignObj->campaign)) {
        $this->campaignId = (int)$this->campaignObj->campaign['id'];
        $this->locale = $this->campaignObj->getLanguage();
      } else {
        $tx->rollback();
        header('HTTP/1.1 503 Men at work');
        return;
      }

      switch ($param->action_type) {
        case 'petition':
          $result = $this->choosePetitionMode($param, $this->campaignObj->campaign['campaign_type_id']);
          break;

        case 'share':
          $result = $this->addActivity($param, 'share');
          break;

        case 'tweet':
          $result = $this->addActivity($param, 'Tweet');
          break;

        case 'facebook':
          $result = $this->addActivity($param, 'Facebook');
          break;

        case 'call':
          CRM_Core_Error::debug_var('SPEAKCIVI CALL $param', $param);
          $result = $this->addActivity($param, 'Phone Call');
          break;

        case 'speakout':
          $result = $this->addActivity($param, 'Email');
          break;

        case 'donate':
          $result = $this->donate($param);
          break;

        case 'poll':
          CRM_Core_Error::debug_log_message('SPEAKCIVI KNOWN UNSUPPORTED EVENT, DISCARDED: ' . $param->action_type);
          $result = 1;
          break;

        default:
          $result = -1;
      }
    });
    return $result;
  }


  /**
   *  Setting up default values for parameters.
   */
  function setDefaults() {
    $this->optIn = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'opt_in');
    $this->groupId = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'group_id');
    $this->noMemberGroupId = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'no_member_group_id');
    $this->noMemberCampaignType = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'no_member_campaign_type');
    $this->thresholdVeryOldActivity = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'threshold_very_old_activity');
    $this->defaultCampaignTypeId = CRM_Core_PseudoConstant::getKey('CRM_Campaign_BAO_Campaign', 'campaign_type_id', 'Petitions');
    $this->countryLangMapping = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'country_lang_mapping');
    $this->genderFemaleValue = CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Contact', 'gender_id', 'Female');
    $this->genderMaleValue = CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Contact', 'gender_id', 'Male');
    $this->genderUnspecifiedValue = CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Contact', 'gender_id', 'unspecified');
  }


  /**
   *
   * @param $param
   */
  function setVeryOldActivity($param) {
    if ($this->thresholdVeryOldActivity > 0) {
      $cd = new DateTime(substr($param->create_dt, 0, 10));
      $cd->modify('+' . $this->thresholdVeryOldActivity . ' days');
      if ($cd->format('Y-m-d') < date('Y-m-d')) {
        $this->useAsCurrentActivity = false;
      }
    }
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
          $this->countryIsoCode = strtoupper($matches[1]);
          $this->postalCode = mb_substr(trim($matches[2]), 0, 12);
        } else {
          $this->postalCode = mb_substr(trim($zip), 0, 12);
          $this->countryIsoCode = strtoupper(@$param->cons_hash->addresses[0]->country);
        }
      }
      if ($this->countryIsoCode) {
        $this->countryIsoCode = ($this->countryIsoCode == 'UK' ? 'GB' : $this->countryIsoCode);
        $this->countryId = CRM_Speakcivi_Logic_Cache_Country::getCountryId($this->countryIsoCode);
      }
    }
  }


  /**
   * Choose petition mode (standard or NoMember).
   *
   * @param object $param
   * @param int $campaignType
   *
   * @return int 1 ok, 0 failed
   */
  public function choosePetitionMode($param, $campaignType) {
    if ($campaignType == $this->noMemberCampaignType) {
      return $this->petitionNoMember($param);
    } else {
      return $this->petition($param);
    }
  }


  /**
   * Create a petition in Civi: contact and activity
   *
   * @param $param
   *
   * @return int 1 ok, 0 failed
   * @throws \CiviCRM_API3_Exception
   */
  public function petition($param) {
    $contact = $this->createContact($param, $this->groupId);
    $isContactNeedConfirmation = CRM_Speakcivi_Logic_Contact::isContactNeedConfirmation($this->newContact, $contact['id'], $this->groupId, $contact['is_opt_out']);
    switch ($this->consentStatus) {
      case CRM_Speakcivi_Logic_Consent::STATUS_ACCEPTED:
        if ($isContactNeedConfirmation) {
          // completed new member + sharing
          $activityStatus = 'optin';
          $this->confirmationBlock = FALSE;
        }
        else {
          // completed + sharing
          $activityStatus = 'Completed';
          $this->confirmationBlock = FALSE;
        }
        break;

      case CRM_Speakcivi_Logic_Consent::STATUS_REJECTED:
        // optout + no any email
        $activityStatus = 'optout';
        $this->confirmationBlock = FALSE;
        break;

      // the same as CRM_Speakcivi_Logic_Consent::STATUS_NOTPROVIDED
      default:
        if ($isContactNeedConfirmation) {
          // scheduled + confirmation
          $activityStatus = 'Scheduled';
          $this->confirmationBlock = TRUE;
        }
        else {
          // completed + sharing
          $activityStatus = 'Completed';
          $this->confirmationBlock = FALSE;
        }
        break;
    }

    $activity = $this->createActivity($param, $contact['id'], 'Petition', $activityStatus);
    CRM_Speakcivi_Logic_Activity::setSourceFields($activity['id'], @$param->source);
    if ($this->newContact) {
      CRM_Speakcivi_Logic_Contact::setContactCreatedDate($contact['id'], $activity['values'][0]['activity_date_time']);
      CRM_Speakcivi_Logic_Contact::setSourceFields($contact['id'], @$param->source);
    }

    $h = $param->cons_hash;
    if ($this->useAsCurrentActivity) {
      if ($this->consentStatus == CRM_Speakcivi_Logic_Consent::STATUS_NOTPROVIDED) {
        $sendResult = $this->sendEmail($this->isAnonymous, $h->emails[0]->email, $contact['id'], $activity['id'], $this->campaignId, $this->confirmationBlock, false);
      }
      else {
        $language = substr($this->locale, 0, 2);
        $pagePost = new CRM_Speakcivi_Page_Post();
        $rlg = $pagePost->setLanguageGroup($contact['id'], $language);
        $pagePost->setLanguageTag($contact['id'], $language);
        $contactCustoms = [];
        $joinSubject = 'optIn:0';
        foreach ($this->consents as $consent) {
          if ($consent->level == CRM_Speakcivi_Logic_Consent::STATUS_ACCEPTED) {
            CRM_Speakcivi_Logic_Activity::dpa($consent, $contact['id'], $this->campaignId, 'Completed');
            $contactCustoms = [
              'is_opt_out' => 0,
              CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_date') => $consent->date,
              CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_version') => $consent->version,
              CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_language') => strtoupper($consent->language),
              CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_utm_source') => $consent->utmSource,
              CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_utm_medium') => $consent->utmMedium,
              CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_utm_campaign') => $consent->utmCampaign,
              CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_campaign_id') => $this->campaignId,
            ];
            $joinSubject = $consent->method;
          }
          if ($consent->level == CRM_Speakcivi_Logic_Consent::STATUS_REJECTED) {
            CRM_Speakcivi_Logic_Activity::dpa($consent, $contact['id'], $this->campaignId, 'Cancelled');
            $contactCustoms = [
              'is_opt_out' => 1,
              CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_date') => '',
              CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_version') => '',
              CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_language') => '',
              CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_utm_source') => '',
              CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_utm_medium') => '',
              CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_utm_campaign') => '',
              CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_consent_campaign_id') => '',
            ];
          }
        }
        if ($contact['preferred_language'] != $this->locale && $rlg == 1) {
          $contactCustoms['preferred_language'] = $this->locale;
        }
        if ($contactCustoms) {
          CRM_Speakcivi_Logic_Contact::set($contact['id'], $contactCustoms);
        }
        if ($this->addJoinActivity) {
          CRM_Speakcivi_Logic_Activity::join($contact['id'], $joinSubject, $this->campaignId);
        }
        if ($this->consentStatus == CRM_Speakcivi_Logic_Consent::STATUS_ACCEPTED) {
          $share_utm_source = 'new_' . str_replace('gb', 'uk', strtolower($this->countryIsoCode)) . '_member';
          $sendResult = $this->sendEmail($this->isAnonymous, $h->emails[0]->email, $contact['id'], $activity['id'], $this->campaignId, $this->confirmationBlock, FALSE, $share_utm_source);
        }
        else {
          // workaround for rejected status
          $sendResult = [];
          $sendResult['values'] = 1;
        }
      }
      if ($sendResult['values'] == 1) {
        return 1;
      }
      return 0;
    }
    else {
      return 1;
    }
  }


  /**
   * Create a petition in Civi: contact and activity but don't add for our
   * members (no groups, no tags)
   *
   * @param $param
   *
   * @return int 1 ok, 0 failed
   * @throws \CiviCRM_API3_Exception
   */
  public function petitionNoMember($param) {
    $contact = $this->createContact($param, $this->noMemberGroupId);

    $optInForActivityStatus = $this->optIn;
    if (!CRM_Speakcivi_Logic_Contact::isContactNeedConfirmation($this->newContact, $contact['id'], $this->noMemberGroupId, $contact['is_opt_out'])) {
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
    if ($this->useAsCurrentActivity) {
      if ($this->optIn == 1) {
        $sendResult = $this->sendEmail($this->isAnonymous, $h->emails[0]->email, $contact['id'], $activity['id'], $this->campaignId, $this->confirmationBlock, true);
      } else {
        $share_utm_source = 'new_'.str_replace('gb', 'uk', strtolower($this->countryIsoCode)).'_member';
        $sendResult = $this->sendEmail($this->isAnonymous, $h->emails[0]->email, $contact['id'], $activity['id'], $this->campaignId, false, true, $share_utm_source);
      }
      if ($sendResult['values'] == 1) {
        return 1;
      }
      return 0;
    } else {
      return 1;
    }
  }


  /**
   * Create a activity
   *
   * @param array $param Params from speakout
   * @param string $type Type name of activity
   * @param string $status Status name of activity
   *
   * @return int 1 ok, 0 failed
   * @throws \CiviCRM_API3_Exception
   */
  public function addActivity($param, $type, $status = 'Completed') {
    $groupId = $this->determineGroupId();
    $contact = $this->createContact($param, $groupId);
    $activity = $this->createActivity($param, $contact['id'], $type, $status);
    CRM_Speakcivi_Logic_Activity::setSourceFields($activity['id'], @$param->source);
    CRM_Speakcivi_Logic_Activity::setShareFields($activity['id'], @$param->metadata->tracking_codes);
    if ($activity['is_error'] == 0) {
      return 1;
    }
    return 0;
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
   * @return int 1 ok, 0 failed
   */
  public function donate($param) {
    if ($param->metadata->status == "success") {
      $groupId = $this->determineGroupId();
      $contact = $this->createContact($param, $groupId);
      $contribution = $this->createContribution($param, $contact["id"]);
      if ($this->newContact) {
        CRM_Speakcivi_Logic_Contact::setContactCreatedDate($contact['id'], $contribution['values'][0]['receive_date']);
      }
      return 1;
    } else {
      CRM_Core_Error::debug_var("SPEAKCIVI", "Ignoring failed donation from " . $param->cons_hash->emails[0]->email);
      return 0;
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
    //TODO make these ids configurable
    $financialTypeId = 1; 
    if ($this->campaignObj->campaign['campaign_type_id'] == $this->distributedCampaignTypeId) {
      $financialTypeId = 9;  //crowdfunding
    }
    $paymentInstrumentId = "Credit Card"; 
    $this->bark("Donation for campaign: " . $this->campaignId);
    $params = array(
      'sequential' => 1,
      'source_contact_id' => $contactId,
      'contact_id' => $contactId,
      'contribution_campaign_id' => $this->campaignId,
      'financial_type_id' => $financialTypeId,
      'payment_instrument_id' => $paymentInstrumentId,
      'receive_date' => $param->create_dt,
      'total_amount' => $param->metadata->amount,
      'fee_amount' => $param->metadata->amount_charged,
      'net_amount' => ($param->metadata->amount - $param->metadata->amount_charged),
      'trxn_id' => $param->metadata->transaction_id,
      'contribution_status' => 'Completed',
      'currency' => $param->metadata->currency,
      'subject' => $param->action_name,
      'location' => $param->action_technical_type,
      'api.Contribution.sendconfirmation' => array(
        'receipt_from_email' => $this->campaignObj->getSenderMail(),
        'receipt_update' => 1,
      ),
    );
    CRM_Speakcivi_Logic_Contribution::setSourceFields($params, @$param->source);
    return civicrm_api3('Contribution', 'create', $params);
  }


  /**
   * Determine members group id based on campaign type.
   *
   * @return int
   */
  private function determineGroupId() {
    if ($this->campaignObj->campaign['campaign_type_id'] == $this->noMemberCampaignType) {
      return $this->noMemberGroupId;
    }
    return $this->groupId;
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
    if ($this->isAnonymous = CRM_Speakcivi_Logic_Contact::isAnonymous($param)) {
      return CRM_Speakcivi_Logic_Contact::getAnonymous();
    }
    $h = $param->cons_hash;
    $contact = array(
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
      'return' => 'id,email,first_name,last_name,preferred_language,is_opt_out',
    );

    $contacIds = CRM_Speakcivi_Logic_Contact::getContactByEmail($h->emails[0]->email);
    if (is_array($contacIds) && count($contacIds) > 0) {
      $contactParam = $contact;
      $contactParam['id'] = array('IN' => array_keys($contacIds));
      unset($contactParam['email']); // getting by email (pseudoconstant) sometimes doesn't work
      $result = civicrm_api3('Contact', 'get', $contactParam);
      if ($result['count'] == 1) {
        $contact = $this->prepareParamsContact($param, $contact, $groupId, $result, $result['id']);
        if (!CRM_Speakcivi_Logic_Contact::needUpdate($contact)) {
          return $result['values'][$result['id']];
        }
      } elseif ($result['count'] > 1) {
        $lastname = $this->cleanLastname($h->lastname);
        $newContact = $contact;
        $newContact['first_name'] = $h->firstname;
        $newContact['last_name'] = $lastname;
        $similarity = $this->glueSimilarity($newContact, $result['values']);
        unset($newContact);
        $contactIdBest = $this->chooseBestContact($similarity);
        $contact = $this->prepareParamsContact($param, $contact, $groupId, $result, $contactIdBest);
        if (!CRM_Speakcivi_Logic_Contact::needUpdate($contact)) {
          return $result['values'][$contactIdBest];
        }
      }
    } else {
      $this->newContact = true;
      $contact = $this->prepareParamsContact($param, $contact, $groupId);
    }
    $result = civicrm_api3('Contact', 'create', $contact);
    return $result['values'][$result['id']];
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
      if (CRM_Speakcivi_Logic_Consent::isExplicitOptIn($this->consents) && $existingContact[$this->apiGroupContactGet]['count'] == 0) {
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
      if (CRM_Speakcivi_Logic_Consent::isExplicitOptIn($this->consents)) {
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
      if (($existingContact[$this->apiAddressGet]['values'][0]['postal_code'] != $this->postalCode) ||
        ($existingContact[$this->apiAddressGet]['values'][0]['country_id'] != $this->countryId)
      ) {
        $contact[$this->apiAddressCreate]['id'] = $existingContact[$this->apiAddressGet]['id'];
        $contact[$this->apiAddressCreate]['postal_code'] = $this->postalCode;
        $contact[$this->apiAddressCreate]['country_id'] = $this->countryId;
      }
    } elseif ($existingContact[$this->apiAddressGet]['count'] > 1) {
      // from speakout we have only (postal_code) or (postal_code and country)
      foreach ($existingContact[$this->apiAddressGet]['values'] as $k => $v) {
        $adr = $this->getAddressValues($v);
        if (
          array_key_exists('country_id', $adr) && $this->countryId == $adr['country_id'] &&
          array_key_exists('postal_code', $adr) && $this->postalCode == $adr['postal_code']
        ) {
          // return without any modification, needed address already exists
          return $contact;
        }
      }
      $postal = false;
      foreach ($existingContact[$this->apiAddressGet]['values'] as $k => $v) {
        $adr = $this->getAddressValues($v);
        if (
          !array_key_exists('country_id', $adr) &&
          array_key_exists('postal_code', $adr) && $this->postalCode == $adr['postal_code']
        ) {
          $contact[$this->apiAddressCreate]['id'] = $v['id'];
          $contact[$this->apiAddressCreate]['country'] = $this->countryIsoCode;
          $postal = true;
          break;
        }
      }
      if (!$postal) {
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
    $contact[$this->apiAddressCreate]['country'] = $this->countryIsoCode;
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
    if (array_key_exists($this->apiAddressCreate, $contact)) {
      if (array_key_exists('postal_code', $contact[$this->apiAddressCreate]) && $contact[$this->apiAddressCreate]['postal_code'] == '') {
        unset($contact[$this->apiAddressCreate]['postal_code']);
      }
      if (array_key_exists('country', $contact[$this->apiAddressCreate]) && $contact[$this->apiAddressCreate]['country'] == '') {
        unset($contact[$this->apiAddressCreate]['country']);
      }
      if (array_key_exists('country_id', $contact[$this->apiAddressCreate]) && $contact[$this->apiAddressCreate]['country_id'] == 0) {
        unset($contact[$this->apiAddressCreate]['country_id']);
      }
      if (array_key_exists('id', $contact[$this->apiAddressCreate]) && count($contact[$this->apiAddressCreate]) == 1) {
        unset($contact[$this->apiAddressCreate]['id']);
      }
      if (count($contact[$this->apiAddressCreate]) == 0) {
        unset($contact[$this->apiAddressCreate]);
      }
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
    $activityTypeId = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', $activityType);
    $activityStatusId = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', $activityStatus);
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
      'details' => $this->determineDetails($param),
    );
    return CRM_Speakcivi_Logic_Activity::setActivity($params);
  }


  /**
   * Determine comment.
   *
   * @param $param
   *
   * @return mixed
   */
  private function determineDetails($param) {
    $details = '';
    if (property_exists($param, 'comment') && $param->comment != '') {
      $details = trim($param->comment);
    }
    if (property_exists($param, 'metadata')) {
      if (property_exists($param->metadata, 'sign_comment') && $param->metadata->sign_comment != '') {
        $details = trim($param->metadata->sign_comment);
      }
      if (property_exists($param->metadata, 'mail_to_subject') && property_exists($param->metadata, 'mail_to_body')) {
        $details = trim($param->metadata->mail_to_subject) . "\n\n" . trim($param->metadata->mail_to_body);
      }
    }
    return $details;
  }


  /**
   * Send email to nonanonymous contact.
   *
   * @param $isAnonymous
   * @param $email
   * @param $contactId
   * @param $activityId
   * @param $campaignId
   * @param $confirmationBlock
   * @param $noMember
   * @param string $share_utm_source
   *
   * @return array
   */
  public function sendEmail($isAnonymous, $email, $contactId, $activityId, $campaignId, $confirmationBlock, $noMember, $share_utm_source = '') {
    if ($isAnonymous) {
      return array('values' => 1);
    } else {
      return $this->sendConfirm($email, $contactId, $activityId, $campaignId, $confirmationBlock, $noMember, $share_utm_source);
    }
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
