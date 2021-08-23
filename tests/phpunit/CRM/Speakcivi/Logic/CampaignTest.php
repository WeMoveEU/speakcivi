<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
class CRM_Speakcivi_Logic_CampaignTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()
      ->install(['eu.wemove.gidipirus', 'eu.wemove.contributm', 'org.project60.sepa', 'mjwshared', 'com.drastikbydesign.stripe', 'eu.wemove.we-act'])
      ->callback(function ($ctx) {
        CRM_WeAct_Upgrader::setRequiredSettingsForTests($ctx);
      }, 8)
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp() : void {
    parent::setUp();
    $this->externalId = 42;
    $campaign_result = civicrm_api3('Campaign', 'create', [
      'campaign_type_id' => 1, 'title' => 'Transient campaign', 'external_identifier' => $this->externalId
    ]);
    $this->campaignId = $campaign_result['id'];
  }

  public function testContructRetrievesExistingCampaign() {
    $campLogic = new CRM_Speakcivi_Logic_Campaign($this->campaignId);
    $this->assertNotNull($campLogic->campaign);
    $this->assertEquals($this->campaignId, $campLogic->campaign['id']);
  }

  public function testCanReceiveCampaignByExternalId() {
    $campaignCache = new CRM_WeAct_CampaignCache(Civi::cache(), new \GuzzleHttp\Client());
    $campLogic = new CRM_Speakcivi_Logic_Campaign();
    $campLogic->campaign = $campaignCache->getOrCreateSpeakout("https://what.ever", $this->externalId);
    $this->assertNotNull($campLogic->campaign);
    $this->assertEquals($this->campaignId, $campLogic->campaign['id']);
  }

}
