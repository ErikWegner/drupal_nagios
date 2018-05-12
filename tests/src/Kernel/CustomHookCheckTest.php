<?php

namespace Drupal\Tests\nagios\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\nagios\Controller\StatuspageController;

/**
 * Tests if external module's hook is executed
 *
 * @group nagios
 */
class CustomHookCheckTest extends KernelTestBase {

  /**
   * Prevent errors due to incomplete schema
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['nagios', 'nagios_hook_test_module'];

  /**
   * Perform any initial set up tasks that run before every test method
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig('nagios');
    StatuspageController::setNagiosStatusConstants();
  }

  public function testHooksInAnotherModule() {
    $results = nagios_invoke_all();

    /** @noinspection PhpUnitTestsInspection */
    self::assertTrue(empty($results['nagios_hook_test_module']));

    $config = \Drupal::configFactory()->getEditable('nagios.settings');
    $config->set('nagios.enable.nagios_hook_test_module', 1);
    $config->save();
    $results = nagios_invoke_all();
    $expected = [
      'status' => 1,
      'type' => 'state',
      'text' => 'Text description for the problem',
    ];
    self::assertSame($expected, $results["nagios_hook_test_module"]['NAGIOS_CHECK_KEY']);
  }
}

