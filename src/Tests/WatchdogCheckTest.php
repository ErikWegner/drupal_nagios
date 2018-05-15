<?php

namespace Drupal\nagios\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Tests the functionality to monitor watchdog items
 *
 * @group nagios
 */
class WatchdogCheckTest extends WebTestBase {

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

  public function testWatchdogCheck() {
    // run check, expect nothing

    // produce a watchdog item

    // run check, expect message

    // run check, excpect message

  }

  public function testWatchdogLimit() {
    // update watchdog settings

    // run check, expect no message

    // produce a watchdog item

    // run check, expect message

    // run check, expect no message
  }
  
  public function testWatchdogLevel() {
    // run check, expect nothing

    // produce a watchdog item with level warning

    // run check with option set to error, expect no message
    
    // run check with option set to warning, expect a message
    
  }
}
