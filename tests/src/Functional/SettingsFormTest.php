<?php

namespace Drupal\Tests\nagios\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the settings form functionality
 *
 * @group nagios
 */
class SettingsFormTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['nagios'];

  /**
   * A simple user with 'administer site configuration' permission
   *
   * @var AccountInterface
   */
  private $settingsUser;

  /**
   * A user with 'administer nagios ignore' permission
   *
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
    $this->settingsUser = $this->drupalCreateUser(['administer site configuration']);
    $this->modulesUser = $this->drupalCreateUser(['administer nagios ignore']);
  }

  /**
   * Tests that the 'admin/config/system/nagios' path returns the right content
   */
  public function testSettingsPageExists() {
    $this->drupalLogin($this->settingsUser);

    $this->drupalGet(self::SETTINGS_PATH);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Unique ID');
  }

  /**
   * Tests that the 'admin/config/system/nagios/ignoredmodules' path returns
   * the right content
   */
  public function testIgnoredModulesPageExists() {
    $this->drupalLogin($this->modulesUser);

    $this->drupalGet(self::IGNORED_MODULES_PATH);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()
      ->pageTextContains('Select those modules that should be ignored for requirement checks.');
  }

  /**
   * Test required permissions for the settings page
   */
  public function testSettingsPagePermissions() {
    $this->drupalLogin($this->settingsUser);
    $this->drupalGet(self::SETTINGS_PATH);
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogin($this->modulesUser);
    $this->drupalGet(self::SETTINGS_PATH);
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test required permissions for the page 'Ignored modules'
   */
  public function testIgnoredModulesPagePermissions() {
    $this->drupalLogin($this->settingsUser);
    $this->drupalGet(self::IGNORED_MODULES_PATH);
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->modulesUser);
    $this->drupalGet(self::IGNORED_MODULES_PATH);
    $this->assertSession()->statusCodeEquals(200);
  }
}
