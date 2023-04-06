<?php

/**
 * Create a gorup of people who clicked on a fundraiser, but didn't donate.
 *
 */
function civicrm_api3_donation_drop_offs_abandons($params) {


  $interval = 'INTERVAL 24 HOUR';

  if (array_key_exists('interval', $params)) {
    $interval = str_replace('_', ' ', $params['interval']);
  }

  CRM_Core_Error::debug_log_message(
    "[speakcivi.shopping_cart_abandons] Creating group for Donate drop offs");

  // Haven't donated in X - below is didn't donate after a click
  //
  // LEFT JOIN civicrm_contribution gave ON (contact.id = gave.contact_id AND gave.receive_date > NOW() - {$interval})
  // LEFT JOIN civicrm_contribution gave_recur ON (contact.id = gave_recur.contact_id AND gave_recur.receive_date > NOW() - {$interval})


   $dao = CRM_Core_DAO::executeQuery( <<<SQL
SELECT DISTINCT email.contact_id,
    civicrm_mailing.id mailing_id
FROM civicrm_mailing
    JOIN civicrm_campaign ON (
        civicrm_mailing.campaign_id = civicrm_campaign.id
    )
    JOIN civicrm_mailing_trackable_url url ON (url.mailing_id = civicrm_mailing.id)
    JOIN civicrm_mailing_event_trackable_url_open click ON (
      click.trackable_url_id = url.id
      AND url.url NOT LIKE '%optout%'
      AND url.url NOT LIKE '%unsubscribe%'
    )
    JOIN civicrm_mailing_event_queue send ON (send.id = event_queue_id)
    JOIN civicrm_contact contact ON (send.contact_id = contact.id)
    JOIN civicrm_email email ON (email.contact_id = contact.id)
    LEFT JOIN civicrm_contribution gave ON (contact.id = gave.contact_id AND gave.receive_date > click.time_stamp)
    LEFT JOIN civicrm_contribution_recur gave_recur ON (contact.id = gave_recur.contact_id AND gave_recur.create_date > click.time_stamp)
WHERE civicrm_campaign.title like 'Fundraising%'
    AND click.time_stamp > NOW() - {$interval}
    AND gave.id IS NULL
    AND gave_recur.id IS NULL

SQL
    );

    $contacts = array();
    while ($dao->fetch()) {
      array_push( $contacts, $dao->contact_id);
    }

    if (count($contacts) == 0) {
      return civicrm_api3_create_success("Nobody found.");
    }

    $group_name = "zzz-temp-" . date('Y-m-d') . " Donate Drop-offs";
    $existing = civicrm_api3('Group', 'get', array("title" => $group_name));
    if ($existing['count'] == 1) {
      // CRM_Core_Error::debug_log_message(json_encode($existing));
      $group_id = (int) $existing['id'];
    } else {
      $params = array(
        'sequential' => 1,
        'title' => $group_name,
        'group_type' => CRM_Core_DAO::VALUE_SEPARATOR . '2' . CRM_Core_DAO::VALUE_SEPARATOR,
        'visibility' => 'User and User Admin Only',
        'source' => 'speakcivi',
      );
      $result = civicrm_api3('Group', 'create', $params);
      $group_id = (int) $result['id'];
    }

    # stuff all the member ids into an array, hope there aren't too many!!
    $insert_contacts = array();
    foreach ($contacts as $contact_id) {
      $insert_contacts[] = "(" . $contact_id  . ",$group_id,'Added')";
    }


    # insert the new members into the new group
    $values = implode(",", $insert_contacts);
    $insert = <<<SQL
            INSERT IGNORE INTO civicrm_group_contact
            (contact_id, group_id, status)
            VALUES
            $values
SQL;
    CRM_Core_DAO::executeQuery($insert);
    CRM_Core_Error::debug_log_message("$group_name created! Found " . count($contacts));

    return civicrm_api3_create_success("$group_name created! Found " . count($contacts));
  }