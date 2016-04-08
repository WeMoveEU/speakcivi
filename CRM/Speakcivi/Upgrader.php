<?php

/**
 * Collection of upgrade steps.
 */
class CRM_Speakcivi_Upgrader extends CRM_Speakcivi_Upgrader_Base {

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
