<?php

namespace Drupal\nagios\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Tests the functionality to monitor cron
 *
 * @group nagios
 */
class CronCheckTest extends WebTestBase {

    /**
     * Modules to install.
     *
     * @var array
     */
    public static $modules = array('nagios');

    /**
     * Perform any initial set up tasks that run before every test method
     */
    public function setUp() {
      parent::setUp();
    }

    public function testCronCheck() {
      // set last run to an old date
      \Drupal::state()->set('system.cron_last', 0);

      // run check function, expect warning
      $result1 = nagios_check_cron();
      $this->assertEqual($result1['data']['status'], 2, "Check critical response");

      // run cron
      $this->cronRun();

      // run check function, expect no warning
      $result2 = nagios_check_cron();
      $this->assertEqual($result2['data']['status'], 0, "Check ok response");
    }
}
