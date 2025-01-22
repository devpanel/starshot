<?php

declare(strict_types=1);

namespace Drupal\Tests\project_browser\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Provides tests for the Project Browser Plugins.
 *
 * @group project_browser
 */
class ProjectBrowserPluginTest extends WebDriverTestBase {

  use ProjectBrowserUiTestTrait;

  // Could be moved into trait under PHP 8.3.
  protected const SECURITY_OPTION_SELECTOR = 'select[name="securityCoverage"] ';
  protected const MAINTENANCE_OPTION_SELECTOR = 'select[name="maintenanceStatus"] ';
  protected const DEVELOPMENT_OPTION_SELECTOR = 'select[name="developmentStatus"] ';
  protected const OPTION_CHECKED = 'option:checked';
  protected const OPTION_FIRST_CHILD = 'option:first-child';
  protected const OPTION_LAST_CHILD = 'option:last-child';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'project_browser',
    'project_browser_devel',
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
    // Update configuration, enable only random_data source.
    $this->config('project_browser.admin_settings')->set('enabled_sources', ['random_data'])->save(TRUE);
  }

  /**
   * Tests the Random Data plugin.
   */
  public function testRandomDataPlugin(): void {
    $assert_session = $this->assertSession();

    $this->getSession()->resizeWindow(1280, 960);
    $this->drupalGet('admin/modules/browse/random_data');
    $this->svelteInitHelper('css', '#project-browser .pb-project--grid');
    $this->assertEquals('Grid', $this->getElementText('#project-browser .pb-display__button[value="Grid"]'));
    $assert_session->waitForElementVisible('css', '#project-browser .pb-project');
    $this->assertTrue($assert_session->waitForText('Results'));
    $assert_session->pageTextNotContains('No modules found');
  }

  /**
   * Tests the available categories.
   */
  public function testCategories(): void {
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/modules/browse/random_data');
    $this->svelteInitHelper('css', '.pb-filter__checkbox');
    $assert_session->elementsCount('css', '.pb-filter__checkbox', 20);
  }

  /**
   * Tests paging through results.
   *
   * We want to click through things and make sure that things are functional.
   * We don't care about the number of results.
   */
  public function testPaging(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/modules/browse/random_data');
    // Immediately clear filters so there are enough visible to enable paging.
    $this->svelteInitHelper('test', 'Clear Filters');
    $this->svelteInitHelper('css', '.pager__item--next');
    $assert_session->elementsCount('css', '.pager__item--next', 1);

    $page->find('css', 'a[aria-label="Next page"]')->click();
    $this->assertNotNull($assert_session->waitForElement('css', '.pager__item--previous'));
    $assert_session->elementsCount('css', '.pager__item--previous', 1);
  }

  /**
   * Tests advanced filtering.
   */
  public function testAdvancedFiltering(): void {
    $this->drupalGet('admin/modules/browse/random_data');
    $this->svelteInitHelper('text', 'Results');

    $this->assertEquals('Show projects covered by a security policy', $this->getElementText(self::SECURITY_OPTION_SELECTOR . self::OPTION_CHECKED));
    $this->assertEquals('Show actively maintained projects', $this->getElementText(self::MAINTENANCE_OPTION_SELECTOR . self::OPTION_CHECKED));
    $this->assertEquals('Show all', $this->getElementText(self::DEVELOPMENT_OPTION_SELECTOR . self::OPTION_CHECKED));

    $page = $this->getSession()->getPage();
    // Clear the security covered filter.
    $page->selectFieldOption('securityCoverage', 'Show all');
    // Set the development status filter.
    $page->selectFieldOption('developmentStatus', 'Show projects under active development');

    // Clear all filters.
    $this->pressWithWait('Clear filters');
    $this->assertEquals('Show all', $this->getElementText(self::SECURITY_OPTION_SELECTOR . self::OPTION_CHECKED));
    $this->assertEquals('Show all', $this->getElementText(self::MAINTENANCE_OPTION_SELECTOR . self::OPTION_CHECKED));
    $this->assertEquals('Show all', $this->getElementText(self::DEVELOPMENT_OPTION_SELECTOR . self::OPTION_CHECKED));

    // Reset to recommended filters.
    $this->pressWithWait('Recommended filters');
    $this->assertEquals('Show projects covered by a security policy', $this->getElementText(self::SECURITY_OPTION_SELECTOR . self::OPTION_CHECKED));
    $this->assertEquals('Show actively maintained projects', $this->getElementText(self::MAINTENANCE_OPTION_SELECTOR . self::OPTION_CHECKED));
    $this->assertEquals('Show all', $this->getElementText(self::DEVELOPMENT_OPTION_SELECTOR . self::OPTION_CHECKED));
  }

  /**
   * Tests broken images.
   */
  public function testBrokenImages(): void {
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/modules/browse/random_data');
    $this->svelteInitHelper('css', 'img[src$="images/puzzle-piece-placeholder.svg"]');

    // RandomData always give an image URL. Sometimes it is a fake URL on
    // purpose so it 404s. This check means that the original image was not
    // found and it was replaced by the placeholder.
    $assert_session->elementExists('css', 'img[src$="images/puzzle-piece-placeholder.svg"]');
  }

  /**
   * Tests the not-compatible flag.
   */
  public function testNotCompatibleText(): void {
    $this->drupalGet('admin/modules/browse/random_data');
    $this->svelteInitHelper('css', '.project_status-indicator');
    $this->assertEquals($this->getElementText('.project_status-indicator .visually-hidden') . ' Not compatible', $this->getElementText('.project_status-indicator'));
  }

  /**
   * Tests the detail page.
   */
  public function testDetailPageRandomDataPlugin(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('admin/modules/browse/random_data');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#project-browser .pb-project'));
    $this->assertTrue($assert_session->waitForText('Results'));

    $assert_session->waitForElementVisible('css', '.pb-project .pb-project__title');
    $first_project_selector = $page->find('css', '.pb-project .pb-project__title .pb-project__link');
    $first_project_selector->click();
    $this->assertTrue($assert_session->waitForText('sites report using this module'));
  }

}
