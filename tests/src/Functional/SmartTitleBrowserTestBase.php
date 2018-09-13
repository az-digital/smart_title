<?php

namespace Drupal\Tests\smart_title\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base class to provide common test setup for Smart Title functional tests.
 */
abstract class SmartTitleBrowserTestBase extends BrowserTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * The standard modules to be loaded for all tests.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'field_ui',
    'node',
    'smart_title',
    'views',
  ];

  /**
   * An administrative user for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Setup test.
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('system_main_block');

    $this->config('system.site')->set('page.front', '/node')->save();

    // Create test_block and test_page node types.
    $this->drupalCreateContentType([
      'type' => 'test_page',
      'name' => 'Test page',
      'display_submitted' => FALSE,
    ]);

    // Create users and test node.
    $this->adminUser = $this->drupalCreateUser([
      'access content overview',
      'access content',
      'administer site configuration',
      'administer content types',
      'administer display modes',
      'administer node display',
      'administer node fields',
      'administer node form display',
      'administer nodes',
      'create test_page content',
    ]);
  }

}