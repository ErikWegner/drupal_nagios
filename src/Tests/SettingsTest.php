<?php

namespace Drupal\nagios\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the settings functionality
 *
 * @group nagios
 */
class SettingsTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('nagios');

  /**
   * A simple user with 'administer site configuration' permission
   */
  private $user;

  /**
   * Perform any initial set up tasks that run before every test method
   */
  public function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(array('administer site configuration'));
  }

  /**
   * Tests that the 'admin/config/system/nagios' path returns the right content
   */
  public function testCustomPageExists() {
    $this->drupalLogin($this->user);

    $this->drupalGet('admin/config/system/nagios');
    $this->assertResponse(200);
    $this->assertText('Unique ID');
  }

}
