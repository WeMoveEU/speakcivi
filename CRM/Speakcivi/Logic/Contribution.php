<?php

class CRM_Speakcivi_Logic_Contribution {

  /**
   * Add UTM field values from $fields to $params as custom contribution fields
   */
  public static function setSourceFields(&$params, $fields) {
    $fields = (array)$fields;
    $fieldNames = array(
      'source' => 'field_contribution_source',
      'medium' => 'field_contribution_medium',
      'campaign' => 'field_contribution_campaign',
      'content' => 'field_contribution_content',
    );
    foreach ($fieldNames as $field => $setting) {
      if (array_key_exists($field, $fields) && $fields[$field]) {
	$params[CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', $setting)] = $fields[$field];
      }
    }
  }
}
