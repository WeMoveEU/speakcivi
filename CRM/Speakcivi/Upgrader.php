<?php

/**
 * Collection of upgrade steps.
 */
class CRM_Speakcivi_Upgrader extends CRM_Speakcivi_Upgrader_Base {

  public function createCustomFields() {
    $result = civicrm_api3('CustomGroup', 'get', [ 'name' => "speakout_integration" ]);
    if ($result['count'] == 0) {
      civicrm_api3('CustomGroup', 'create', [
        'title' => "Speakout integration",
        'extends' => "Campaign",
        'name' => "speakout_integration",
        'table_name' => "civicrm_value_speakout_integration_2",
      ]);
    }

    $result = civicrm_api3('CustomField', 'get', [ 'name' => "language_form_url" ]);
    if ($result['count'] == 0) {
      civicrm_api3('CustomField', 'create', [
        'custom_group_id' => "speakout_integration",
        'label' => "Language conversion form URL",
        'name' => "language_form_url",
        'column_name' => "language_form_url",
        'data_type' => "String",
        'html_type' => "Text",
        'is_view' => 0,
        'is_searchable' => 0,
      ]);
    }
  }


  /**
   * Upgrade to version 1.1 (Clean up Join and Leave activities)
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_110() {
    $this->ctx->log->info('Applying update 1.1');
    $this->executeSqlFile('sql/upgrade_110.sql');
    return TRUE;
  }
}
