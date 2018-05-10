<?php

namespace Drupal\nagios\Tests;

use Drupal\KernelTests\KernelTestBase;
use Drupal\nagios\Controller\StatuspageController;

/**
 * Tests the functionality to monitor cron
 *
 * @group nagios
 */
class NagiosCheckTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['nagios'];

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
}

