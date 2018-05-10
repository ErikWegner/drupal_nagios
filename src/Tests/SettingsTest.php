<?php

namespace Drupal\nagios\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Session\AccountInterface;

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
   * @var AccountInterface
   */
  private $settingsUser;

  /**
   * A user with 'administer nagios ignore' permission
   * @var AccountInterface 
   */
  private $modulesUser;

  /**
   * Url to the settings page
   */
  const SETTINGS_PATH = 'admin/config/system/nagios';

  /**
   * Url to the ignored modules page
   */
  const IGNORED_MODULES_PATH = 'admin/config/system/nagios/ignoredmodules';

  /**
   * Perform any initial set up tasks that run before every test method
   */
  public function setUp() {
    parent::setUp();
    $this->settingsUser = $this->drupalCreateUser(array('administer site configuration'));
    $this->modulesUser = $this->drupalCreateUser(array('administer nagios ignore'));
  }

  /**
   * Tests that the 'admin/config/system/nagios' path returns the right content
   */
  public function testSettingsPageExists() {
    $this->drupalLogin($this->settingsUser);

    $this->drupalGet(SettingsTest::SETTINGS_PATH);
    $this->assertResponse(200);
    $this->assertText('Unique ID');
  }
  
  /**
   * Tests that the 'admin/config/system/nagios/ignoredmodules' path returns the right content
   */
  public function testIgnoredModulesPageExists() {
    $this->drupalLogin($this->modulesUser);

    $this->drupalGet(SettingsTest::IGNORED_MODULES_PATH);
    $this->assertResponse(200);
    $this->assertText('Select those modules that should be ignored for requirement checks.');
  }

  /**
   * Test required permissions for the settings page
   */
  public function testSettingsPagePermissions() {
    $this->drupalLogin($this->settingsUser);
    $this->drupalGet(SettingsTest::SETTINGS_PATH);
    $this->assertResponse(200);

    $this->drupalLogin($this->modulesUser);
    $this->drupalGet(SettingsTest::SETTINGS_PATH);
    $this->assertResponse(403);
  }

  /**
   * Test required permissions for the page 'Ignored modules'
   */
  public function testIgnoredModulesPagePermissions() {
    $this->drupalLogin($this->settingsUser);
    $this->drupalGet(SettingsTest::IGNORED_MODULES_PATH);
    $this->assertResponse(403);

    $this->drupalLogin($this->modulesUser);
    $this->drupalGet(SettingsTest::IGNORED_MODULES_PATH);
    $this->assertResponse(200);
  }
}
