<?php

namespace Drupal\Tests\smart_title\Functional;

/**
 * Tests the module's title hide functionality.
 *
 * @group Smart Title
 */
class SmartTitleBasicTest extends SmartTitleBrowserTestBase {

  /**
   * Tests that Smart Title without config doesn't modifies core behavior.
   */
  public function testSmartTitleBasics() {
    $this->drupalLogin($this->adminUser);

    // Node teaser title was displayed on the front page for admin user.
    $this->drupalGet('node');
    $article_title = $this->xpath($this->cssSelectToXpath('article h2'));
    $this->assertEquals($this->testPageNode->label(), $article_title[0]->getText());

    // Node title wasn't displayed on the node's full page for admin user.
    $this->drupalGet('node/' . $this->testPageNode->id());
    $article_title = $this->xpath($this->cssSelectToXpath('article h2'));
    $this->assertEquals($article_title, []);

    $this->drupalLogout();

    // Node teaser title was displayed on the front page for anonymous user.
    $this->drupalGet('node');
    $article_title = $this->xpath($this->cssSelectToXpath('article > h2'));
    $this->assertEquals($this->testPageNode->label(), $article_title[0]->getText());

    // Node title wasn't displayed on the node's full page for anonymous user.
    $this->drupalGet('node/' . $this->testPageNode->id());
    $article_title = $this->xpath($this->cssSelectToXpath('article > h2'));
    $this->assertEquals($article_title, []);

    // Enable Smart Title for the test_page content type.
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('admin/config/content/smart-title', [
      'node_bundles[node:test_page]' => TRUE,
    ], 'Save configuration');
    $this->drupalPostForm('admin/structure/types/manage/test_page/display', [
      'smart_title__enabled' => TRUE,
    ], 'Save');
    $this->drupalPostForm(NULL, [
      'fields[smart_title][weight]' => '-5',
      'fields[smart_title][region]' => 'content',
    ], 'Save');

    // Test that node teaser title isn't displayed on front page for admin user.
    $this->drupalGet('node');
    $web_assert = $this->assertSession();

    $web_assert->elementExists('css', 'article > h2');
    // Test that the expected settings are applied onto the title markup.
    $web_assert->elementNotExists('css', 'article .node__content h2.node__title');

    $this->drupalLogout();

    // Test that node teaser title isn't displayed on front page for anonymous
    // user.
    $this->drupalGet($this->testPageNode->toUrl()->toString());
    $web_assert = $this->assertSession();
    $web_assert->elementExists('css', 'article .node__content h2.node__title');

    // Verify that smart title's link wraps the title field's output, so that
    // it is NOT inside the field element.
    $web_assert->elementExists('css', 'article .node__content h2.node__title > a > .field--name-title');
  }

  /**
   * Test saved configuration.
   */
  public function testSavedConfiguration() {
    $this->drupalLogin($this->adminUser);

    // Enable Smart Title for the test_page content type's teaser.
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('admin/config/content/smart-title', [
      'node_bundles[node:test_page]' => TRUE,
    ], 'Save configuration');
    $this->drupalPostForm('admin/structure/types/manage/test_page/display/teaser', [
      'smart_title__enabled' => TRUE,
    ], 'Save');
    $this->drupalPostForm(NULL, [
      'fields[smart_title][weight]' => '-5',
      'fields[smart_title][region]' => 'content',
    ], 'Save');
    $this->click('[name="smart_title_settings_edit"]');
    $this->drupalPostForm(NULL, [
      'fields[smart_title][settings_edit_form][settings][smart_title__tag]' => 'h3',
      'fields[smart_title][settings_edit_form][settings][smart_title__classes]' => 'smart-title__test',
      'fields[smart_title][settings_edit_form][settings][smart_title__link]' => 0,
    ], 'Save');

    // Verify saved settings.
    $display = \Drupal::entityTypeManager()->getStorage('entity_view_display')->load('node.' . $this->testPageNode->getType() . '.teaser');
    $smart_title_enabled = $display->getThirdPartySetting('smart_title', 'enabled');
    $saved_settings = $display->getThirdPartySetting('smart_title', 'settings');
    $this->assertTrue($smart_title_enabled === TRUE);
    $this->assertTrue($saved_settings === [
      'smart_title__tag' => 'h3',
      'smart_title__classes' => 'smart-title__test',
      'smart_title__link' => FALSE,
    ]);

    // Verify expected field settings summary.
    $web_assert = $this->assertSession();
    $web_assert->elementTextContains('css', '[data-drupal-selector="edit-fields-smart-title"] .field-plugin-summary', 'Tag: H3');
    $web_assert->elementTextContains('css', '[data-drupal-selector="edit-fields-smart-title"] .field-plugin-summary', 'Classes: smart-title__test');
    $web_assert->elementTextNotContains('css', '[data-drupal-selector="edit-fields-smart-title"] .field-plugin-summary', 'Links to entity');

    // Test that Smart Title is displayed on the front page (teaser view mode)
    // for admin user.
    $this->drupalGet('node');
    $this->assertSession()->pageTextContains($this->testPageNode->label());
    $article_title = $this->xpath($this->cssSelectToXpath('article h3.smart-title__test'));
    $this->assertEquals($this->testPageNode->label(), $article_title[0]->getText());

    // Re-save form again.
    $this->drupalGet('admin/structure/types/manage/test_page/display/teaser');
    $this->drupalPostForm(NULL, [], 'Save');

    // Verify saved settings.
    $display = \Drupal::entityTypeManager()->getStorage('entity_view_display')->load('node.' . $this->testPageNode->getType() . '.teaser');
    $smart_title_enabled = $display->getThirdPartySetting('smart_title', 'enabled');
    $saved_settings = $display->getThirdPartySetting('smart_title', 'settings');
    $this->assertTrue($smart_title_enabled === TRUE);
    $this->assertTrue($saved_settings === [
      'smart_title__tag' => 'h3',
      'smart_title__classes' => 'smart-title__test',
      'smart_title__link' => FALSE,
    ]);

    // Re-assert field settings summary.
    $web_assert = $this->assertSession();
    $web_assert->elementTextContains('css', '[data-drupal-selector="edit-fields-smart-title"] .field-plugin-summary', 'Tag: H3');
    $web_assert->elementTextContains('css', '[data-drupal-selector="edit-fields-smart-title"] .field-plugin-summary', 'Classes: smart-title__test');
    $web_assert->elementTextNotContains('css', '[data-drupal-selector="edit-fields-smart-title"] .field-plugin-summary', 'Links to entity');

    // Test that Smart Title is displayed on the front page (teaser view mode)
    // for admin user with the expected values.
    $this->drupalGet('node');
    $this->assertSession()->pageTextContains($this->testPageNode->label());
    $article_title = $this->xpath($this->cssSelectToXpath('article h3.smart-title__test'));
    $this->assertEquals($this->testPageNode->label(), $article_title[0]->getText());
  }

}
