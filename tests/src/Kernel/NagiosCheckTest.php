<?php

namespace Drupal\nagios\Tests;

use Drupal\Core\Access\AccessResultNeutral;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\nagios\Controller\StatuspageController;

/**
 * Tests the functionality to monitor cron
 *
 * @group nagios
 */
class NagiosCheckTest extends EntityKernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['nagios', 'user'];

  /**
   * Perform any initial set up tasks that run before every test method
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig('nagios');
    StatuspageController::setNagiosStatusConstants();
  }

  public function testCronCheck() {
    // set last run to an old date
    \Drupal::state()->set('system.cron_last', 0);

    // run check function, expect warning
    $result1 = nagios_check_cron();
    self::assertSame(2, $result1['data']['status'], "Check critical response");

    // run cron
    /** @var \Drupal\Core\CronInterface $cron */
    $cron = \Drupal::service('cron');
    $cron->run();

    // run check function, expect no warning
    $result2 = nagios_check_cron();
    self::assertSame(0, $result2['data']['status'], "Check ok response");
  }

  public function testStatuspage() {
    $statuspage_controller = StatuspageController::create(\Drupal::getContainer());
    $_SERVER['HTTP_USER_AGENT'] = 'Test';
    self::assertContains(
      "nagios=UNKNOWN, DRUPAL:UNKNOWN=Unauthorized |",
      $statuspage_controller->content()->getContent());

    $_SERVER['HTTP_USER_AGENT'] = 'Nagios';
    self::assertContains(
      "nagios=OK,",
      $statuspage_controller->content()->getContent());

    $config = \Drupal::configFactory()->getEditable('nagios.settings');
    $config->set('nagios.statuspage.getparam', TRUE);
    $config->save();
    $_SERVER['HTTP_USER_AGENT'] = 'Test';
    self::assertContains(
      "nagios=UNKNOWN, DRUPAL:UNKNOWN=Unauthorized |",
      $statuspage_controller->content()->getContent());

    $_GET['unique_id'] = 'Nagios';
    self::assertContains(
      "nagios=OK,",
      $statuspage_controller->content()->getContent());

    self::assertInstanceOf(AccessResultNeutral::class, $statuspage_controller->access());
    self::assertFalse($statuspage_controller->access()->isAllowed());

    $config->set('nagios.statuspage.enabled', TRUE);
    $config->save();
    self::assertTrue($statuspage_controller->access()->isAllowed());
  }
}

