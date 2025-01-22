<?php

declare(strict_types=1);

namespace Drupal\Tests\project_browser\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Provides tests for the Project Browser Example plugins.
 *
 * @group project_browser
 */
class ProjectBrowserExamplePluginTest extends WebDriverTestBase {

  use ProjectBrowserUiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'project_browser',
    'project_browser_source_example',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser([
      'administer modules',
      'administer site configuration',
    ]));
    // Update configuration, enable only example source.
    $this->config('project_browser.admin_settings')->set('enabled_sources', ['project_browser_source_example'])->save(TRUE);
  }

  /**
   * Tests the Example plugin.
   */
  public function testExamplePlugin(): void {
    $assert_session = $this->assertSession();

    $this->getSession()->resizeWindow(1280, 960);
    $this->drupalGet('admin/modules/browse/project_browser_source_example');
    $this->svelteInitHelper('css', '#project-browser .pb-project--grid');
    $this->assertEquals('Grid', $this->getElementText('#project-browser .pb-display__button[value="Grid"]'));
    $assert_session->waitForElementVisible('css', '#project-browser .pb-project');
    $this->assertTrue($assert_session->waitForText('Project 1'));
    $assert_session->pageTextNotContains('No modules found');
    $this->svelteInitHelper('css', '.pb-filter__checkbox');
    $assert_session->elementsCount('css', '.pb-filter__checkbox', 2);
  }

}
