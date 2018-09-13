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
    // Add test node.
    $node_1_page = $this->drupalCreateNode(['type' => 'test_page']);

    // Node teaser title was displayed on the front page for admin user.
    $this->drupalGet('node');
    $article_title = $this->xpath($this->cssSelectToXpath('article h2'));
    $this->assertEquals($node_1_page->label(), $article_title[0]->getText());

    // Node title wasn't displayed on the node's full page for admin user.
    $this->drupalGet('node/' . $node_1_page->id());
    $article_title = $this->xpath($this->cssSelectToXpath('article h2'));
    $this->assertEquals($article_title, []);

    $this->drupalLogout();

    // Node teaser title was displayed on the front page for anonymous user.
    $this->drupalGet('node');
    $article_title = $this->xpath($this->cssSelectToXpath('article > h2'));
    $this->assertEquals($node_1_page->label(), $article_title[0]->getText());

    // Node title wasn't displayed on the node's full page for anonymous user.
    $this->drupalGet('node/' . $node_1_page->id());
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
    $this->drupalGet($node_1_page->toUrl()->toString());
    $web_assert = $this->assertSession();
    $web_assert->elementExists('css', 'article .node__content h2.node__title');
  }

}
