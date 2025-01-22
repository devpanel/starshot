<?php

declare(strict_types=1);

namespace Drupal\Tests\project_browser\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

// cspell:ignore cashpresso Adnuntius Paypage Redsys ZURB Superfish TMGMT Toki
// cspell:ignore Webtheme Pitchburgh Gotem Webform Bsecurity Bstatus Cardless

/**
 * ProjectBrowserUITest refactored to use the Drupal.org JSON API endpoint.
 *
 * @group project_browser
 */
class ProjectBrowserUiTestJsonApi extends WebDriverTestBase {

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
    'project_browser_test',
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
    $this->config('project_browser.admin_settings')->set('enabled_sources', ['drupalorg_jsonapi'])->save(TRUE);
    $this->drupalLogin($this->drupalCreateUser([
      'administer modules',
      'administer site configuration',
    ]));
  }

  /**
   * Tests the display of the error message sent from Drupal.org.
   */
  public function testErrorMessageWhenWrongDrupalVersion(): void {
    // Fake the Drupal version.
    $this->container->get('state')->set('project_browser:test_deprecated_api', TRUE);

    $assert_session = $this->assertSession();

    $this->drupalGet('admin/modules/browse/drupalorg_jsonapi');
    $this->svelteInitHelper('text', 'Unsupported version');
  }

  /**
   * Tests the grid view.
   */
  public function testGrid(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->getSession()->resizeWindow(1250, 1000);
    $this->drupalGet('admin/modules/browse/drupalorg_jsonapi');
    $this->svelteInitHelper('css', '.pb-project.pb-project--grid');
    $assert_session->waitForElementVisible('css', '#project-browser .pb-display__button[value="Grid"]');
    $grid_text = $this->getElementText('#project-browser .pb-display__button[value="Grid"]');
    $this->assertEquals('Grid', $grid_text);
    $this->assertTrue($assert_session->waitForText('Results'));
    $assert_session->pageTextNotContains('No records available');
    $page->pressButton('List');
    $this->assertNotNull($assert_session->waitForElementVisible('css', '#project-browser .pb-project.pb-project--list'));
    $assert_session->elementsCount('css', '#project-browser .pb-project.pb-project--list', 12);
    $page->pressButton('Grid');
    $this->assertNotNull($assert_session->waitForElementVisible('css', '#project-browser .pb-project.pb-project--grid'));
    $this->getSession()->resizeWindow(1100, 1000);
    $assert_session->assertNoElementAfterWait('css', '.toggle.list-button');
    $this->assertNotNull($assert_session->waitForElementVisible('css', '#project-browser .pb-project.pb-project--list'));
    $assert_session->elementsCount('css', '#project-browser .pb-project.pb-project--list', 12);
    $this->getSession()->resizeWindow(1210, 1210);
    $this->assertNotNull($assert_session->waitForElementVisible('css', '#project-browser .pb-project.pb-project--grid'));
    $assert_session->elementsCount('css', '#project-browser .pb-project.pb-project--grid', 12);
  }

  /**
   * Tests the available categories.
   */
  public function testCategories(): void {
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/modules/browse/drupalorg_jsonapi');
    $this->svelteInitHelper('css', '.pb-filter__multi-dropdown input[type="checkbox"]');
    $assert_session->elementsCount('css', '.pb-filter__multi-dropdown input[type="checkbox"]', 19);
  }

  /**
   * Tests the clickable category functionality.
   */
  public function testClickableCategory(): void {
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/modules/browse/drupalorg_jsonapi');
    $this->svelteInitHelper('text', 'Token');
    $assert_session->waitForButton('Token')->click();

  }

  /**
   * Tests category filtering.
   */
  public function testCategoryFiltering(): void {
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/modules/browse/drupalorg_jsonapi');
    $this->svelteInitHelper('css', '.pb-filter__multi-dropdown');
    // Initial results count on page load.
    $this->assertTrue($assert_session->waitForText(' Results'));
    $assert_session->pageTextNotContains(' 0 Results');
    // Open category drop-down.
    $this->clickWithWait('.pb-filter__multi-dropdown', 'E-commerce', TRUE);

    $this->svelteInitHelper('css', '#0cd80c8e-5c20-43a8-aa3e-ec701007d443');
    // Click 'E-commerce' checkbox.
    $this->clickWithWait('#0cd80c8e-5c20-43a8-aa3e-ec701007d443');

    // Use blur event to close drop-down so Clear is visible.
    $this->assertSession()->elementExists('css', '.pb-filter__multi-dropdown')->blur();

    $module_category_e_commerce_filter_selector = 'p.filter-applied:nth-child(1)';
    // Make sure the 'E-commerce' module category filter is applied.
    $this->assertEquals('E-commerce', $this->getElementText("$module_category_e_commerce_filter_selector .filter-applied__label"));
    $assert_session->pageTextContains(' Results');
    $assert_session->pageTextNotContains(' 0 Results');

    // Clear the checkbox to verify the results revert to their initial state.
    $this->clickWithWait('.pb-filter__multi-dropdown', 'E-commerce', TRUE);
    $this->svelteInitHelper('css', '#0cd80c8e-5c20-43a8-aa3e-ec701007d443');
    $this->clickWithWait('#0cd80c8e-5c20-43a8-aa3e-ec701007d443', ' Results');
    $assert_session->pageTextNotContains(' 0 Results');
    $this->assertSession()->elementExists('css', '.pb-filter__multi-dropdown')->blur();

    $this->pressWithWait('Clear filters', ' Results');
    $assert_session->pageTextNotContains(' 0 Results');

    // Open category drop-down.
    $assert_session->elementExists('css', '.pb-filter__multi-dropdown')->click();

    // Click 'Media' checkbox.
    $this->clickWithWait('#68428c33-1db7-438d-b1b3-e23004e0982b');
    $this->assertTrue($assert_session->waitForText(' Results'));
    $assert_session->pageTextNotContains(' 0 Results');

    // Click 'Developer tools' checkbox.
    $this->clickWithWait('#086cebcf-200f-4c34-886e-f9921919b292');

    // Make sure the 'Media' module category filter is applied.
    $this->assertEquals('Media', $this->getElementText('p.filter-applied:nth-child(2) .filter-applied__label'));
    $this->assertTrue($assert_session->waitForText(' Results'));
    $assert_session->pageTextNotContains(' 0 Results');
  }

  /**
   * Tests the Target blank functionality.
   */
  public function testTargetBlank(): void {
    $assert_session = $this->assertSession();
    $this->drupalGet('admin/modules/browse/drupalorg_jsonapi');
    $this->svelteInitHelper('text', 'Token');
    $assert_session->waitForButton('Token')->click();
  }

  /**
   * Tests paging through results.
   */
  public function testPaging(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/modules/browse/drupalorg_jsonapi');
    $this->svelteInitHelper('text', ' Results');
    $assert_session->pageTextNotContains(' 0 Results');
    $this->assertPagerItems(['1', '2', '3', '4', '5', '…', 'Next', 'Last']);

    $page->pressButton('Clear filters');
    $this->assertTrue($assert_session->waitForText(' Results'));
    $assert_session->pageTextNotContains(' 0 Results');
    $this->assertPagerItems(['1', '2', '3', '4', '5', '…', 'Next', 'Last']);
    $assert_session->elementExists('css', '.pager__item--active > .is-active[aria-label="Page 1"]');

    $this->clickWithWait('[aria-label="Next page"]');
    $assert_session->pageTextContains(' Results');
    $assert_session->pageTextNotContains(' 0 Results');
    $this->assertPagerItems(['First', 'Previous', '1', '2', '3', '4', '5', '6', '…', 'Next', 'Last']);

    $this->clickWithWait('[aria-label="Next page"]');
    $assert_session->pageTextContains(' Results');
    $assert_session->pageTextNotContains(' 0 Results');
    $this->assertPagerItems(['First', 'Previous', '1', '2', '3', '4', '5', '6', '7', '…', 'Next', 'Last']);

    // Ensure that when the number of projects is even divisible by the number
    // shown on a page, the pager has the correct number of items.
    $this->clickWithWait('[aria-label="First page"]');

    // Open category drop-down.
    $assert_session->elementExists('css', '.pb-filter__multi-dropdown')->click();

    // Click 'Accessibility' checkbox.
    $this->clickWithWait('#3df293b3-c9a1-4232-962b-3c8169e8e6e3', '', TRUE);

    // Click 'E-commerce' checkbox.
    $this->clickWithWait('#0cd80c8e-5c20-43a8-aa3e-ec701007d443', bypass_wait: TRUE);

    // Click 'Media' checkbox.
    $this->clickWithWait('#68428c33-1db7-438d-b1b3-e23004e0982b', ' Results', TRUE);
    $assert_session->pageTextNotContains(' 0 Results');
    $this->assertPagerItems(['1', '2', '3', '4', '5', '…', 'Next', 'Last']);
  }

  /**
   * Tests advanced filtering.
   */
  public function testAdvancedFiltering(): void {
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/modules/browse/drupalorg_jsonapi');
    $this->svelteInitHelper('text', 'Token');
    $this->pressWithWait('Clear filters');
    $this->pressWithWait('Recommended filters');
    $assert_session->pageTextContains(' Results');
    $assert_session->pageTextNotContains(' 0 Results');

    // Make sure the second filter applied is the security covered filter.
    $this->assertTrue($assert_session->optionExists('securityCoverage', 'Show projects covered by a security policy')->isSelected());

    // Clear the security covered filter.
    $this->clickWithWait(self::SECURITY_OPTION_SELECTOR . self::OPTION_LAST_CHILD);
    $assert_session->pageTextContains(' Results');
    $assert_session->pageTextNotContains(' 0 Results');

    // Click the Active filter.
    $assert_session->waitForElementVisible('css', self::DEVELOPMENT_OPTION_SELECTOR);
    $this->clickWithWait(self::DEVELOPMENT_OPTION_SELECTOR . self::OPTION_FIRST_CHILD);

    // Make sure the correct filter was applied.
    $this->assertEquals('Show projects under active development', $this->getElementText(self::DEVELOPMENT_OPTION_SELECTOR . self::OPTION_CHECKED));
    $assert_session->waitForText('No records available');

    // Clear all filters.
    $this->pressWithWait('Clear filters', 'Results');

    // Click the Actively maintained filter.
    $this->clickWithWait(self::MAINTENANCE_OPTION_SELECTOR . self::OPTION_FIRST_CHILD, ' Results');
    $assert_session->pageTextNotContains(' 0 Results');
    $this->assertEquals('Show actively maintained projects', $this->getElementText(self::MAINTENANCE_OPTION_SELECTOR . self::OPTION_CHECKED));
    $assert_session->pageTextContains(' Results');
    $assert_session->pageTextNotContains(' 0 Results');
  }

  /**
   * Tests sorting criteria.
   */
  public function testSortingCriteria(): void {
    $assert_session = $this->assertSession();
    // Clear filters.
    $this->drupalGet('admin/modules/browse/drupalorg_jsonapi');
    $this->svelteInitHelper('text', 'Clear Filters');
    $this->pressWithWait('Clear filters');

    // Select 'Recently created' option.
    $this->sortBy('created');
    $assert_session->pageTextContains(' Results');
    $assert_session->pageTextNotContains(' 0 Results');
  }

  /**
   * Tests search with strings that need URI encoding.
   */
  public function testSearchForSpecialChar(): void {
    $this->markTestSkipped('We are using mocks of real data from Drupal.org, what we currently have does not have content suitable for this test.');
  }

  /**
   * Tests the detail page.
   */
  public function testDetailPage(): void {
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/modules/browse/drupalorg_jsonapi');
    $this->svelteInitHelper('text', 'Token');
    $assert_session->waitForButton('Token')->click();
  }

  /**
   * Tests that filtering, sorting, paging persists.
   */
  public function testPersistence(): void {
    $this->markTestSkipped('Testing this with the JSON Api endpoint is not needed. The feature is not source dependent.');
  }

  /**
   * Tests recommended filters.
   */
  public function testRecommendedFilter(): void {
    $assert_session = $this->assertSession();
    // Clear filters.
    $this->drupalGet('admin/modules/browse/drupalorg_jsonapi');
    $this->svelteInitHelper('text', 'Clear Filters');
    $this->pressWithWait('Clear filters', 'Results');
    $this->pressWithWait('Recommended filters');

    // Check that the actively maintained tag is present.
    $this->assertTrue($assert_session->optionExists('maintenanceStatus', 'Show actively maintained projects')->isSelected());
    // Make sure the second filter applied is the security covered filter.
    $this->assertTrue($assert_session->optionExists('securityCoverage', 'Show projects covered by a security policy')->isSelected());
    $this->assertTrue($assert_session->waitForText(' Results'));
    $assert_session->pageTextNotContains(' 0 Results');
  }

  /**
   * Tests filters are displayed if they are defined by source.
   */
  public function testFiltersShownIfDefinedBySource(): void {
    if (version_compare(\Drupal::VERSION, '10.3', '<')) {
      $this->markTestSkipped('This test requires Drupal 10.3 or later.');
    }
    $assert_session = $this->assertSession();
    // Enable module for extra source plugin.
    $this->container->get('module_installer')->install(['project_browser_devel']);
    $this->config('project_browser.admin_settings')
      ->set('enabled_sources', ['recipes', 'project_browser_test_mock'])
      ->save();

    $this->drupalGet('admin/modules/browse/recipes');
    // Recipes doesn't define any filters so no filters are displayed.
    $this->assertNull($assert_session->waitForElementVisible('css', '.search__form-filters-container'));

    // Set the names of filters which will be defined by the test mock.
    // @see \Drupal\project_browser_test\Plugin\ProjectBrowserSource\ProjectBrowserTestMock::getFilterDefinitions()
    $filters_to_define = ['maintenanceStatus', 'securityCoverage'];
    \Drupal::state()->set('filters_to_define', $filters_to_define);

    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    // Drupal.org test mock defines only two filters (actively maintained filter
    // and security coverage filter).
    $assert_session->waitForElementVisible('css', '.search__form-filters-container');
    $this->assertTrue($assert_session->waitForText('Maintenance status'));
    $assert_session->waitForElementVisible('css', self::MAINTENANCE_OPTION_SELECTOR);
    $this->assertTrue($assert_session->waitForText('Security advisory coverage'));
    $assert_session->waitForElementVisible('css', self::SECURITY_OPTION_SELECTOR);
    // Make sure no other filters are displayed.
    $this->assertFalse($assert_session->waitForText('Development status'));
    $this->assertNull($assert_session->waitForElementVisible('css', self::DEVELOPMENT_OPTION_SELECTOR));
    $this->assertFalse($assert_session->waitForText('Filter by category'));
    // Make sure category filter element is not visible.
    $this->assertNull($assert_session->waitForElementVisible('css', 'div.search__form-filters-container > div.search__form-filters > section > fieldset > div'));
  }

  /**
   * Tests the view mode toggle keeps its state.
   */
  public function testToggleViewState(): void {
    $assert_session = $this->assertSession();
    $viewSwitches = [
      [
        'selector' => '.pb-display__button[value="Grid"]',
        'value' => 'Grid',
      ], [
        'selector' => '.pb-display__button[value="List"]',
        'value' => 'List',
      ],
    ];
    $this->getSession()->resizeWindow(1300, 1300);

    foreach ($viewSwitches as $selector) {
      $this->drupalGet('admin/modules/browse/drupalorg_jsonapi');
      $this->svelteInitHelper('css', $selector['selector']);
      $this->getSession()->getPage()->pressButton($selector['value']);
      $this->svelteInitHelper('text', 'Token');
      $assert_session->waitForButton('Token')->click();
      $this->svelteInitHelper('text', 'Close');
      $assert_session->waitForButton('Close')->click();
      $this->assertSession()->elementExists('css', $selector['selector'] . '.pb-display__button--selected');
    }
  }

  /**
   * Tests tabledrag on configuration page.
   */
  public function testTabledrag(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->container->get('module_installer')->install([
      'block',
      'project_browser_devel',
    ]);
    $this->drupalPlaceBlock('local_tasks_block');

    $this->drupalGet('admin/modules/browse/drupalorg_jsonapi');
    $local_tasks = $assert_session->elementExists('css', 'h2:contains("Primary tabs") + ul')
      ->findAll('css', 'li a[href*="/admin/modules/browse/"]');
    $this->assertCount(2, $local_tasks);
    // Verify that the mocked source is first tab.
    $this->assertSame('Browse', $local_tasks[0]->getText());
    $assert_session->waitForElementVisible('css', '.pb-display__button');

    // Re-order plugins.
    $this->drupalGet('admin/config/development/project_browser');
    $first_plugin = $page->find('css', '#source--drupalorg_jsonapi');
    $second_plugin = $page->find('css', '#source--random_data');
    $first_plugin->find('css', '.tabledrag-handle')->dragTo($second_plugin);
    $this->assertTableRowWasDragged($first_plugin);
    $this->submitForm([], 'Save');

    // Verify that Random data is first tab.
    $this->drupalGet('admin/modules/browse/drupalorg_jsonapi');
    $assert_session->waitForElementVisible('css', '#project-browser .pb-project');
    $first_tab = $page->find('css', '.pb-tabs__link:nth-child(1)');
    $this->assertSame('Random data', $local_tasks[0]->getText());

    // Disable the mock plugin.
    $this->drupalGet('admin/config/development/project_browser');
    $enabled_row = $page->find('css', '#source--drupalorg_jsonapi');
    $disabled_region_row = $page->find('css', '.status-title-disabled');
    $enabled_row->find('css', '.handle')->dragTo($disabled_region_row);
    $this->assertTableRowWasDragged($enabled_row);
    $this->submitForm([], 'Save');
    $assert_session->pageTextContains('The configuration options have been saved.');

    // Verify that only Random data plugin is enabled.
    $this->drupalGet('admin/modules/browse/random_data');
    $this->svelteInitHelper('css', '.pb-filter__multi-dropdown input[type="checkbox"]');
    $assert_session->elementsCount('css', '.pb-filter__multi-dropdown input[type="checkbox"]', 20);

    $this->config('project_browser.admin_settings')->set('enabled_sources', ['project_browser_test_mock'])->save(TRUE);
    $this->drupalGet('admin/config/development/project_browser');
    $this->assertTrue($assert_session->optionExists('edit-enabled-sources-project-browser-test-mock-status', 'enabled')->isSelected());
    $this->assertTrue($assert_session->optionExists('edit-enabled-sources-random-data-status', 'disabled')->isSelected());

    // Verify that only the mock plugin is enabled.
    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('css', '.pb-filter__multi-dropdown input[type="checkbox"]');
    $assert_session->elementsCount('css', '.pb-filter__multi-dropdown input[type="checkbox"]', 19);
  }

}
