<?php

require_once 'speakcivi.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function speakcivi_civicrm_config(&$config) {
  _speakcivi_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function speakcivi_civicrm_xmlMenu(&$files) {
  _speakcivi_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function speakcivi_civicrm_install() {
  _speakcivi_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function speakcivi_civicrm_uninstall() {
  _speakcivi_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function speakcivi_civicrm_enable() {
  _speakcivi_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function speakcivi_civicrm_disable() {
  _speakcivi_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function speakcivi_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _speakcivi_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function speakcivi_civicrm_managed(&$entities) {
  _speakcivi_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function speakcivi_civicrm_caseTypes(&$caseTypes) {
  _speakcivi_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function speakcivi_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _speakcivi_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implementation of hook_civicrm_tokens
 *
 * @param $tokens
 */
function speakcivi_civicrm_tokens(&$tokens) {
  $tokens['speakcivi']['speakcivi.confirmation_hash'] = 'Confirmation Hash';
  $tokens['speakcivi']['member_city'] = 'Member City';
}

/**
 * Implementation of hook_civicrm_tokenValues
 *
 * @param $values
 * @param $cids
 * @param null $job
 * @param array $tokens
 * @param null $context
 */
function speakcivi_civicrm_tokenValues(&$values, $cids, $job = null, $tokens = array(), $context = null) {
  foreach ($cids as $cid) {
    $values[$cid]['speakcivi.confirmation_hash'] = sha1(CIVICRM_SITE_KEY . $cid);
  }

  //  cargo cult wants me to use this too: || in_array('member_city', $tokens['speakcivi']
  if (array_key_exists('member_city', $tokens['speakcivi'])) {
    speakcivi_member_city($values, $cids, $context);
  }
}


function speakcivi_member_city(&$values, $cids, $context) {
    /* 
    if ($context ) {
        foreach($context as $k => $v) {
            CRM_Core_Error::debug_log_message("Context has $k $v");
        }
    }
    else {
            CRM_Core_Error::debug_log_message("Context is NULL");
    }
    */
	$dao = CRM_Utils_SQL_Select::from('civicrm_contact cc')
		->join('ca', 'LEFT JOIN civicrm_address ca on cc.id=ca.contact_id and ca.is_primary=1')
		->join('country', 'LEFT JOIN civicrm_country country on country.id=country_id')
		->select('contact_id, preferred_language, city, country.iso_code')
        ->where('cc.id IN (#cids)', array('cids' => $cids))
        ->execute();

    $FALLBACK_CITY_VALUES = array(
        "fr_FR" => "votre ville",
        "de_DE" => "deine Stadt",
        "en_GB" => "your city",
        "en_US" => "your city",
        "pl_PL" => "Twoje miasto",
    );

    while ($dao->fetch()) {
        // not sure i get how this logic works
        $city = $dao->city;
        if ($city) {
            $values[$dao->contact_id]['speakcivi.member_city'] = $city;
            CRM_Core_Error::debug_log_message("CITY =  $city");
        }
        else {
            $city = $FALLBACK_CITY_VALUES[$dao->preferred_language];
            CRM_Core_Error::debug_log_message($dao->preferred_language . " & " . $city);
            CRM_Core_Error::debug_log_message($dao->preferred_language . " & $city");
            $values[$dao->contact_id]['speakcivi.member_city'] = $city;
        }
    }
}

/*
 * Implementation of hook_civicrm_container
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_container
 */
function speakcivi_civicrm_container($container) {
    $container->addResource(new \Symfony\Component\Config\Resource\FileResource(__FILE__));
    $container->findDefinition('dispatcher')->addMethodCall('addListener',
      ['civi.token.list', 'speakcivi_register_tokens']
    );
    $container->findDefinition('dispatcher')->addMethodCall('addListener',
      ['civi.token.eval', 'speakcivi_evaluate_tokens']
    );
    CRM_Core_Error::debug_log_message("All configured up.");
}
  
function speakcivi_register_tokens(\Civi\Token\Event\TokenRegisterEvent $e) {
    CRM_Core_Error::debug_log_message("OHAI, I'm the SpeakCivi token registry.");
    $e->entity('profile')
      ->register('speakcivi', 'member_city', 'Member City'); // ts('Member City'));
}
  
function speakcivi_evaluate_tokens(\Civi\Token\Event\TokenValueEvent $e) {
  CRM_Core_Error::debug_log_message("OHAI, I'm the SpeakCivi token evaluator.");
  foreach ($e->getRows() as $row) {
      /** @var TokenRow $row */
      $row->format('text/html');
      $row->tokens('speakcivi', 'member_city', $row->context['city'] || 'your City'); // ts('your City'));
  }
}

function speakcivi_civicrm_apiWrappers(&$wrappers, $apiRequest) {
  if ($apiRequest['entity'] == 'MailingEventUnsubscribe' && $apiRequest['action'] == 'create') {
    $wrappers[] = new CRM_Speakcivi_APIWrapper();
  }
}

/**
 * Implements hook_civicrm_preProcess().
 */
function speakcivi_civicrm_preProcess($formName, &$form) {
  if (in_array($formName, array('CRM_Contribute_Form_Contribution_Main'))) {
    CRM_Core_Resources::singleton()->addScriptFile('eu.wemove.speakcivi', 'js/speakcivi-prefill.js');
  }
}
