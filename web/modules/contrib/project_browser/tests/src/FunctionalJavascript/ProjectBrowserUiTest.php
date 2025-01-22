<?php

declare(strict_types=1);

namespace Drupal\Tests\project_browser\FunctionalJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\Core\Extension\MissingDependencyException;
use Drupal\Core\Recipe\Recipe;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\project_browser\EnabledSourceHandler;

// cspell:ignore coverageall doomer eggman quiznos statusactive statusmaintained
// cspell:ignore vetica

/**
 * Provides tests for the Project Browser UI.
 *
 * These tests rely on a module that replaces Project Browser data with
 * test data.
 *
 * @see project_browser_test_install()
 *
 * @group project_browser
 */
class ProjectBrowserUiTest extends WebDriverTestBase {

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
    $this->config('project_browser.admin_settings')->set('enabled_sources', ['project_browser_test_mock'])->save(TRUE);
    $this->drupalLogin($this->drupalCreateUser([
      'administer modules',
      'administer site configuration',
    ]));
  }

  /**
   * Tests the grid view.
   */
  public function testGrid(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->getSession()->resizeWindow(1250, 1000);
    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('css', '.pb-project.pb-project--grid');
    $this->assertNotEmpty($assert_session->waitForButton('Grid'));
    $this->svelteInitHelper('text', '10 Results');
    $assert_session->elementsCount('css', '#project-browser .pb-project.pb-project--grid', 10);
    $this->assertTrue($assert_session->waitForText('Results'));
    $assert_session->pageTextNotContains('No modules found');
    $page->pressButton('List');
    $this->assertNotNull($assert_session->waitForElementVisible('css', '#project-browser .pb-project.pb-project--list'));
    $assert_session->elementsCount('css', '#project-browser .pb-project.pb-project--list', 10);
    $page->pressButton('Grid');
    $this->assertNotNull($assert_session->waitForElementVisible('css', '#project-browser .pb-project.pb-project--grid'));
    $this->getSession()->resizeWindow(1100, 1000);
    $assert_session->assertNoElementAfterWait('css', '.pb-display__button[value="List"]');
    $this->assertNotNull($assert_session->waitForElementVisible('css', '#project-browser .pb-project.pb-project--list'));
    $assert_session->elementsCount('css', '#project-browser .pb-project.pb-project--list', 10);
    $this->getSession()->resizeWindow(1210, 1210);
    $this->assertNotNull($assert_session->waitForElementVisible('css', '#project-browser .pb-project.pb-project--grid'));
    $assert_session->elementsCount('css', '#project-browser .pb-project.pb-project--grid', 10);
  }

  /**
   * Tests the available categories.
   */
  public function testCategories(): void {
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('css', '.pb-filter__checkbox');
    $assert_session->elementsCount('css', '.pb-filter__checkbox', 19);
  }

  /**
   * Tests the clickable category functionality on module page.
   */
  public function testClickableCategory(): void {
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('text', 'Dancing Queen');

    // Click to open module page.
    $assert_session->waitForButton('Dancing Queen')?->click();
  }

  /**
   * Tests category filtering.
   */
  public function testCategoryFiltering(): void {
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('css', '.pb-filter__multi-dropdown');
    // Initial results count on page load.
    $this->assertTrue($assert_session->waitForText('10 Results'));
    // Open category drop-down.
    $this->clickWithWait('.pb-filter__multi-dropdown', 'E-commerce', TRUE);

    // Click 'E-commerce' checkbox.
    $this->clickWithWait('#104');

    $module_category_e_commerce_filter_selector = 'p.filter-applied:first-child';
    // Make sure the 'E-commerce' module category filter is applied.
    $this->assertEquals('E-commerce', $this->getElementText("$module_category_e_commerce_filter_selector .filter-applied__label"));

    // This call has the second argument, `$reload`, set to TRUE due to it
    // failing on ~2% of GitLabCI test runs. It is not entirely clear why this
    // specific call intermittently fails while others do not. It's known the
    // Svelte app has occasional initialization problems on GitLabCI that are
    // reliably fixed by a page reload, so we allow that here to prevent random
    // failures that are not representative of real world use.
    $this->assertProjectsVisible([
      'Cream cheese on a bagel',
      'Dancing Queen',
      'Kangaroo',
      '9 Starts With a Higher Number',
      'Helvetica',
      'Astronaut Simulator',
    ], TRUE);

    // Clear the checkbox to verify the results revert to their initial state.
    $this->clickWithWait('#104', '10 Results');

    // Use blur event to close drop-down so Clear is visible.
    $this->assertSession()->elementExists('css', '.pb-filter__multi-dropdown')->blur();

    $this->pressWithWait('Clear filters', '25 Results');

    // Open category drop-down again by pressing space.
    $this->assertSession()->elementExists('css', '.pb-filter__multi-dropdown')->keyDown(' ');
    $this->assertSession()->waitForText('Media');

    // Click 'Media' checkbox.
    $this->clickWithWait('#67');

    // Click 'E-commerce' checkbox.
    $this->clickWithWait('#104');

    // Make sure the 'Media' module category filter is applied.
    $this->assertEquals('Media', $this->getElementText('p.filter-applied:nth-child(2) .filter-applied__label'));
    // Assert that only media and administration module categories are shown.
    $this->assertProjectsVisible([
      'Jazz',
      'Eggman',
      'Tooth Fairy',
      'Vitamin&C;$?',
      'Cream cheese on a bagel',
      'Pinky and the Brain',
      'No Scrubs',
      'Soup',
      'Mad About You',
      'Dancing Queen',
      'Kangaroo',
      '9 Starts With a Higher Number',
    ]);
    $this->assertTrue($assert_session->waitForText('20 Results'));
  }

  /**
   * Tests the Target blank functionality.
   */
  public function testTargetBlank(): void {
    $assert_session = $this->assertSession();
    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('text', 'Helvetica');
    $assert_session->waitForButton('Helvetica')?->click();
  }

  /**
   * Tests read-only input fields for referred commands.
   */
  public function testReadonlyFields(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('text', 'Helvetica');

    $assert_session->waitForElementVisible('css', '.project__action_button');
    $page->pressButton('View Commands for Helvetica');

    $command_boxes = $page->waitFor(10, fn ($page) => $page->findAll('css', '.command-box textarea[readonly]'));
    $this->assertCount(2, $command_boxes);

    // The first textarea should have the command to require the module.
    $this->assertSame('composer require drupal/helvetica', $command_boxes[0]->getValue());
    // And the second textarea should have the command to install it.
    $this->assertStringEndsWith('drush install helvetica', $command_boxes[1]->getValue());

    // Tests alt text for copy command image.
    $download_commands = $page->findAll('css', '.command-box img');
    $this->assertCount(2, $download_commands);
    $this->assertEquals('Copy the download command', $download_commands[0]->getAttribute('alt'));
    $this->assertStringStartsWith('Copy the install command', $download_commands[1]->getAttribute('alt'));
  }

  /**
   * Tests paging through results.
   */
  public function testPaging(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('text', '10 Results');

    $this->assertProjectsVisible([
      'Cream cheese on a bagel',
      'Pinky and the Brain',
      'Dancing Queen',
      'Kangaroo',
      '9 Starts With a Higher Number',
      'Octopus',
      'Helvetica',
      'Unwritten&:/',
      'Grapefruit',
      'Astronaut Simulator',
    ]);
    $this->assertPagerItems([]);

    $page->pressButton('Clear filters');
    $this->assertTrue($assert_session->waitForText('25 Results'));
    $this->assertProjectsVisible([
      'Jazz',
      'Eggman',
      'Tooth Fairy',
      'Vitamin&C;$?',
      'Cream cheese on a bagel',
      'Pinky and the Brain',
      'Ice Ice',
      'No Scrubs',
      'Soup',
      'Mad About You',
      'Dancing Queen',
      'Kangaroo',
    ]);
    $this->assertPagerItems(['1', '2', '3', 'Next', 'Last']);
    $assert_session->elementExists('css', '.pager__item--active > .is-active[aria-label="Page 1"]');

    $this->clickWithWait('[aria-label="Next page"]');
    $this->assertProjectsVisible([
      '9 Starts With a Higher Number',
      'Quiznos',
      'Octopus',
      'Helvetica',
      '1 Starts With a Number',
      'Ruh roh',
      'Fire',
      'Looper',
      'Become a Banana',
      'Unwritten&:/',
      'Doomer',
      'Grapefruit',
    ]);
    $this->assertPagerItems(['First', 'Previous', '1', '2', '3', 'Next', 'Last']);

    $this->clickWithWait('[aria-label="Next page"]');
    $this->assertProjectsVisible([
      'Astronaut Simulator',
    ]);
    $this->assertPagerItems(['First', 'Previous', '1', '2', '3']);

    // Ensure that when the number of projects is even divisible by the number
    // shown on a page, the pager has the correct number of items.
    $this->clickWithWait('[aria-label="First page"]');

    // Open category drop-down.
    $assert_session->elementExists('css', '.pb-filter__multi-dropdown')->click();

    // Click 'Media' checkbox.
    $this->clickWithWait('#67', '', TRUE);

    // Click 'E-commerce' checkbox.
    $this->clickWithWait('#104', '', TRUE);

    // Click 'E-commerce' checkbox.
    $this->clickWithWait('#104', '18 results');
    $this->assertPagerItems(['1', '2', 'Next', 'Last']);

    $this->clickWithWait('[aria-label="Next page"]');

    $this->assertPagerItems(['First', 'Previous', '1', '2']);
  }

  /**
   * Tests paging options.
   */
  public function testPagingOptions(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('css', '.pb-project.pb-project--list');
    $this->pressWithWait('Clear filters');
    $assert_session->waitForText('Modules per page');
    $assert_session->elementsCount('css', '#project-browser .pb-project.pb-project--list', 12);
    $assert_session->waitForText('Modules per page');
    $page->selectFieldOption('num-projects', '24');
    $assert_session->waitForElementVisible('css', '#project-browser .pb-project.pb-project--list');
    $assert_session->elementsCount('css', '#project-browser .pb-project.pb-project--list', 24);
  }

  /**
   * Tests advanced filtering.
   */
  public function testAdvancedFiltering(): void {
    $page = $this->getSession()->getPage();

    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('text', 'Astronaut Simulator');
    $this->pressWithWait('Clear filters');
    $this->pressWithWait('Recommended filters');
    $this->assertProjectsVisible([
      'Cream cheese on a bagel',
      'Pinky and the Brain',
      'Dancing Queen',
      'Kangaroo',
      '9 Starts With a Higher Number',
      'Octopus',
      'Helvetica',
      'Unwritten&:/',
      'Grapefruit',
      'Astronaut Simulator',
    ]);

    // Make sure the second filter applied is the security covered filter.
    $this->assertEquals('Show projects covered by a security policy', $this->getElementText(self::SECURITY_OPTION_SELECTOR . self::OPTION_CHECKED));

    // Clear the security covered filter.
    $this->clickWithWait(self::SECURITY_OPTION_SELECTOR . self::OPTION_LAST_CHILD);
    $this->assertProjectsVisible([
      'Jazz',
      'Vitamin&C;$?',
      'Cream cheese on a bagel',
      'Pinky and the Brain',
      'Ice Ice',
      'No Scrubs',
      'Dancing Queen',
      'Kangaroo',
      '9 Starts With a Higher Number',
      'Quiznos',
      'Octopus',
      'Helvetica',
    ]);

    // Check aria-labelledby property for advanced filter.
    foreach ($page->findAll('css', '.filters [role="group"]') as $element) {
      $this->assertSame($element->findAll('xpath', 'div')[0]->getAttribute('id'), $element->getAttribute('aria-labelledby'));
    }

    // Click the Active filter.
    $page->selectFieldOption('developmentStatus', 'Show projects under active development');

    $this->assertProjectsVisible([
      'Jazz',
      'Cream cheese on a bagel',
      'Ice Ice',
      'No Scrubs',
      'Dancing Queen',
      'Kangaroo',
      '9 Starts With a Higher Number',
      'Octopus',
      'Helvetica',
      '1 Starts With a Number',
      'Become a Banana',
      'Grapefruit',
    ]);

    // Click the "Show all" filter for security.
    $this->clickWithWait(self::SECURITY_OPTION_SELECTOR . self::OPTION_LAST_CHILD, '', TRUE);
    $this->assertProjectsVisible([
      'Jazz',
      'Cream cheese on a bagel',
      'Ice Ice',
      'No Scrubs',
      'Dancing Queen',
      'Kangaroo',
      '9 Starts With a Higher Number',
      'Octopus',
      'Helvetica',
      '1 Starts With a Number',
      'Become a Banana',
      'Grapefruit',
    ]);

    // Clear all filters.
    $this->pressWithWait('Clear filters', '25 Results');

    // Click the Actively maintained filter.
    $this->clickWithWait(self::MAINTENANCE_OPTION_SELECTOR . self::OPTION_FIRST_CHILD);
    $this->assertEquals('Show actively maintained projects', $this->getElementText(self::MAINTENANCE_OPTION_SELECTOR . self::OPTION_CHECKED));

    $this->assertProjectsVisible([
      'Jazz',
      'Vitamin&C;$?',
      'Cream cheese on a bagel',
      'Pinky and the Brain',
      'Ice Ice',
      'No Scrubs',
      'Dancing Queen',
      'Kangaroo',
      '9 Starts With a Higher Number',
      'Quiznos',
      'Octopus',
      'Helvetica',
    ]);
  }

  /**
   * Tests sorting criteria.
   */
  public function testSortingCriteria(): void {
    $assert_session = $this->assertSession();
    // Clear filters.
    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('text', 'Clear Filters');
    $this->pressWithWait('Clear filters');
    $assert_session->elementsCount('css', '#pb-sort option', 4);
    $this->assertEquals('Most popular', $this->getElementText('#pb-sort option:nth-child(1)'));
    $this->assertEquals('A-Z', $this->getElementText('#pb-sort option:nth-child(2)'));
    $this->assertEquals('Z-A', $this->getElementText('#pb-sort option:nth-child(3)'));
    $this->assertEquals('Newest first', $this->getElementText('#pb-sort option:nth-child(4)'));

    // Select 'A-Z' sorting order.
    $this->sortBy('a_z');

    // Assert that the projects are listed in ascending order of their titles.
    $this->assertProjectsVisible([
      '1 Starts With a Number',
      '9 Starts With a Higher Number',
      'Astronaut Simulator',
      'Become a Banana',
      'Cream cheese on a bagel',
      'Dancing Queen',
      'Doomer',
      'Eggman',
      'Fire',
      'Grapefruit',
      'Helvetica',
      'Ice Ice',
    ]);

    // Select 'Z-A' sorting order.
    $this->sortBy('z_a');

    $this->assertProjectsVisible([
      'Vitamin&C;$?',
      'Unwritten&:/',
      'Tooth Fairy',
      'Soup',
      'Ruh roh',
      'Quiznos',
      'Pinky and the Brain',
      'Octopus',
      'No Scrubs',
      'Mad About You',
      'Looper',
      'Kangaroo',
    ]);

    // Select 'Active installs' option.
    $this->sortBy('usage_total');

    // Assert that the projects are listed in descending order of their usage.
    $this->assertProjectsVisible([
      'Jazz',
      'Eggman',
      'Tooth Fairy',
      'Vitamin&C;$?',
      'Cream cheese on a bagel',
      'Pinky and the Brain',
      'Ice Ice',
      'No Scrubs',
      'Soup',
      'Mad About You',
      'Dancing Queen',
      'Kangaroo',
    ]);

    // Select 'Newest First' option.
    $this->sortBy('created');

    // Assert that the projects are listed in descending order of their date of
    // creation.
    $this->assertProjectsVisible([
      '9 Starts With a Higher Number',
      'Helvetica',
      'Become a Banana',
      'Ice Ice',
      'Astronaut Simulator',
      'Grapefruit',
      'Fire',
      'Cream cheese on a bagel',
      'No Scrubs',
      'Soup',
      'Octopus',
      'Tooth Fairy',
    ]);
  }

  /**
   * Tests search with strings that need URI encoding.
   */
  public function testSearchForSpecialChar(): void {
    // Clear filters.
    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('text', '10 Results');
    $this->pressWithWait('Clear filters', '25 Results');

    // Fill in the search field.
    $this->inputSearchField('', TRUE);
    $this->inputSearchField('&', TRUE);
    $this->assertSession()->waitForElementVisible('css', ".search__search-submit")->click();
    $this->assertProjectsVisible([
      'Vitamin&C;$?',
      'Unwritten&:/',
    ]);

    // Fill in the search field.
    $this->inputSearchField('', TRUE);
    $this->inputSearchField('n&', TRUE);
    $this->assertSession()->waitForElementVisible('css', ".search__search-submit")->click();
    $this->assertProjectsVisible([
      'Vitamin&C;$?',
      'Unwritten&:/',
    ]);

    $this->inputSearchField('', TRUE);
    $this->inputSearchField('$', TRUE);
    $this->assertSession()->waitForElementVisible('css', ".search__search-submit")->click();
    $this->assertProjectsVisible([
      'Vitamin&C;$?',
    ]);

    $this->inputSearchField('', TRUE);
    $this->inputSearchField('?', TRUE);
    $this->assertSession()->waitForElementVisible('css', ".search__search-submit")->click();
    $this->assertProjectsVisible([
      'Vitamin&C;$?',
    ]);

    $this->inputSearchField('', TRUE);
    $this->inputSearchField('&:', TRUE);
    $this->assertSession()->waitForElementVisible('css', ".search__search-submit")->click();
    $this->assertProjectsVisible([
      'Unwritten&:/',
    ]);

    $this->inputSearchField('', TRUE);
    $this->inputSearchField('$?', TRUE);
    $this->assertSession()->waitForElementVisible('css', ".search__search-submit")->click();
    $this->assertProjectsVisible([
      'Vitamin&C;$?',
    ]);
  }

  /**
   * Tests the detail page.
   */
  public function testDetailPage(): void {
    $assert_session = $this->assertSession();
    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('text', 'Helvetica');
    $assert_session->waitForButton('Helvetica')?->click();
    // Check the detail modal displays.
    $assert_session->waitForElementVisible('xpath', '//span[contains(@class, "ui-dialog-title") and text()="Helvetica"]');
    $assert_session->elementExists('css', 'button.project__action_button');
    // Close the modal.
    $assert_session->waitForButton('Close')?->click();
    $assert_session->elementNotExists('xpath', '//span[contains(@class, "ui-dialog-title") and text()="Helvetica"]');
  }

  /**
   * Tests the detail page.
   */
  public function testReopenDetailModal(): void {
    $assert_session = $this->assertSession();
    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('text', 'Helvetica');
    $assert_session->waitForButton('Helvetica')?->click();
    // Check the detail modal displays.
    $assert_session->waitForElementVisible('xpath', '//span[contains(@class, "ui-dialog-title") and text()="Helvetica"]');
    $assert_session->elementExists('css', 'button.project__action_button');
    // Close the modal and check it no longer exists.
    $assert_session->waitForButton('Close')?->click();
    $assert_session->elementNotExists('xpath', '//span[contains(@class, "ui-dialog-title") and text()="Helvetica"]');
    // Check that a different module modal can be opened.
    $assert_session->waitForButton('Octopus')->click();
    $assert_session->waitForElementVisible('xpath', '//span[contains(@class, "ui-dialog-title") and text()="Octopus"]');
    $assert_session->waitForButton('Close')?->click();
    $assert_session->elementNotExists('xpath', '//span[contains(@class, "ui-dialog-title") and text()="Octopus"]');
    // Check that first detail modal can be reopened.
    $assert_session->waitForElementVisible('xpath', '//span[contains(@class, "ui-dialog-title") and text()="Helvetica"]');
    $assert_session->elementExists('css', 'button.project__action_button');
  }

  /**
   * Tests that filtering, sorting, paging persists.
   */
  public function testPersistence(): void {
    $this->markTestSkipped('Skipped because the persistence layer has been removed for now and needs to be rewritten.');

    $assert_session = $this->assertSession();
    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('text', 'Clear Filters');
    $this->pressWithWait('Clear filters');

    // Select 'Z-A' sorting order.
    $this->sortBy('z_a');

    // Select the active development status filter.
    $assert_session->waitForElementVisible('css', self::DEVELOPMENT_OPTION_SELECTOR);
    $this->clickWithWait(self::DEVELOPMENT_OPTION_SELECTOR . self::OPTION_FIRST_CHILD);

    // Open category drop-down.
    $assert_session->elementExists('css', '.pb-filter__multi-dropdown')->click();

    // Select the E-commerce filter.
    $assert_session->waitForElementVisible('css', '#104');
    $this->clickWithWait('#104', '', TRUE);

    // Select the Media filter.
    $assert_session->waitForElementVisible('css', '#67');
    $this->clickWithWait('#67', '', TRUE);

    $this->assertTrue($assert_session->waitForText('15 Results'));
    $this->assertProjectsVisible([
      'Octopus',
      'No Scrubs',
      'Mad About You',
      'Kangaroo',
      'Jazz',
      'Helvetica',
      'Grapefruit',
      'Eggman',
      'Doomer',
      'Dancing Queen',
      'Cream cheese on a bagel',
      'Become a Banana',
    ]);

    $this->clickWithWait('[aria-label="Next page"]');
    $this->assertProjectsVisible([
      'Astronaut Simulator',
      '9 Starts With a Higher Number',
      '1 Starts With a Number',
    ]);
    $this->getSession()->reload();
    // Should still be on second results page.
    $this->svelteInitHelper('css', '#project-browser .pb-project');
    $this->assertProjectsVisible([
      'Astronaut Simulator',
      '9 Starts With a Higher Number',
      '1 Starts With a Number',
    ]);
    $this->assertTrue($assert_session->waitForText('15 Results'));

    $this->assertEquals('E-commerce', $this->getElementText('p.filter-applied:first-child .filter-applied__label'));
    $this->assertEquals('Media', $this->getElementText('p.filter-applied:nth-child(2) .filter-applied__label'));

    $this->clickWithWait('[aria-label="First page"]');
    $this->assertProjectsVisible([
      'Octopus',
      'No Scrubs',
      'Mad About You',
      'Kangaroo',
      'Jazz',
      'Helvetica',
      'Grapefruit',
      'Eggman',
      'Doomer',
      'Dancing Queen',
      'Cream cheese on a bagel',
      'Become a Banana',
    ], TRUE);

    $this->assertEquals('E-commerce', $this->getElementText('p.filter-applied:first-child .filter-applied__label'));
    $this->assertEquals('Media', $this->getElementText('p.filter-applied:nth-child(2) .filter-applied__label'));
  }

  /**
   * Tests recommended filters.
   */
  public function testRecommendedFilter(): void {
    $assert_session = $this->assertSession();
    // Clear filters.
    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('text', 'Clear Filters');
    $this->pressWithWait('Clear filters', '25 Results');
    $this->pressWithWait('Recommended filters');

    // Check that the actively maintained tag is present.
    $this->assertEquals('Show actively maintained projects', $this->getElementText(self::MAINTENANCE_OPTION_SELECTOR . self::OPTION_CHECKED));
    // Make sure the second filter applied is the security covered filter.
    $this->assertEquals('Show projects covered by a security policy', $this->getElementText(self::SECURITY_OPTION_SELECTOR . self::OPTION_CHECKED));
    $this->assertTrue($assert_session->waitForText('10 Results'));
  }

  /**
   * Tests multiple source plugins at once.
   */
  public function testMultiplePlugins(): void {
    $this->markTestSkipped('This test is skipped because it needs to be rewritten now that in-app tabbing and persistence is removed.');

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    // Enable module for extra source plugin.
    $this->container->get('module_installer')->install(['project_browser_devel'], TRUE);
    // Test categories with multiple plugin enabled.
    $this->drupalGet('admin/modules/browse');
    $this->svelteInitHelper('css', '.pb-filter__checkbox');
    $assert_session->elementsCount('css', '.pb-filter__multi-dropdown__items > div input', 19);

    $this->svelteInitHelper('css', '#project-browser .pb-project');
    // Count tabs.
    $tab_count = $page->findAll('css', '.pb-tabs__link');
    $this->assertCount(2, $tab_count);
    // Get result count for first tab.
    $this->assertEquals('10 Results', $this->getElementText('.pb-search-results'));
    // Get second tab text.
    $second_tab_text = $assert_session->buttonExists('random_data')->getText();

    // Apply filters in project_browser_test_mock(first tab).
    $assert_session->waitForElement('css', '.views-exposed-form__item input[type="checkbox"]');

    $this->pressWithWait('Clear filters', '25 Results');
    // Removing/applying filters will not change second tab results.
    $this->assertSame($second_tab_text, $assert_session->buttonExists('random_data')->getText());

    // Open category drop-down.
    $this->clickWithWait('.pb-filter__multi-dropdown', 'E-commerce', TRUE);

    // Click 'E-commerce' checkbox.
    $this->clickWithWait('#104');

    // Click 'Media' checkbox. It will change results on first tab.
    $this->clickWithWait('#67', '20 Results');
    // Applying filters will not change second tab results.
    $this->assertSame($second_tab_text, $assert_session->buttonExists('random_data')->getText());

    // Use blur event to close drop-down so Clear is visible.
    $this->assertSession()->elementExists('css', '.pb-filter__multi-dropdown')->blur();
    $this->assertSame('2 categories selected', $page->find('css', '.pb-filter__multi-dropdown__label')->getText());

    // Click other tab.
    $this->pressWithWait('random_data');
    $this->svelteInitHelper('css', '.pb-filter__checkbox');
    $assert_session->elementsCount('css', '.pb-filter__multi-dropdown__items > div input', 20);
    $assert_session->waitForElementVisible('css', '#project-browser .pb-project');
    $this->assertNotEquals('9 Results Sorted by Active installs', $this->getElementText('.pb-search-results'));
    // Switching tab will not change result count.
    $this->assertEquals($second_tab_text . ' (active tab)', $page->findButton('random_data')->getText());

    // Open category drop-down again by pressing space.
    $this->assertSession()->elementExists('css', '.pb-filter__multi-dropdown')->keyDown(' ');

    // Apply the second module category filter.
    $second_category_filter_selector = '.pb-filter__multi-dropdown__items > div:nth-child(2) input';
    $this->clickWithWait("$second_category_filter_selector");

    // Assert that the filters persist.
    $second_label_selector = '.pb-filter__multi-dropdown__items > div:nth-child(2) label';
    $second_label_text = $page->find('css', $second_label_selector)->getText();
    $assert_session->fieldExists($second_label_text)->check();

    // Applying filter on second tab will change result count.
    $this->assertNotSame($second_tab_text, $assert_session->buttonExists('random_data')->getText());
    $this->assertSame('1 category selected', $page->find('css', '.pb-filter__multi-dropdown__label')->getText());
    // Save the filter applied in second tab.
    $applied_filter = $this->getElementText('p.filter-applied:nth-child(1) .filter-applied__label');
    // Save the number of results.
    $results_before = count($page->findAll('css', '#project-browser .pb-project.list'));

    // Switch back to first tab.
    $this->pressWithWait('project_browser_test_mock');
    $this->assertSame('2 categories selected', $page->find('css', '.pb-filter__multi-dropdown__label')->getText());
    $first_filter_element = $page->find('css', 'p.filter-applied:nth-child(1)');
    $this->assertEquals('E-commerce', $first_filter_element->find('css', '.filter-applied__label')->getText());
    $second_filter_element = $page->find('css', 'p.filter-applied:nth-child(2)');
    $this->assertEquals('Media', $second_filter_element->find('css', '.filter-applied__label')->getText());

    // Again switch to second tab.
    $this->pressWithWait('random_data');
    // Assert that the filters persist.
    $this->assertEquals($applied_filter, $this->getElementText('p.filter-applied:nth-child(1) .filter-applied__label'));
    $this->assertSame('1 category selected', $page->find('css', '.pb-filter__multi-dropdown__label')->getText());

    // Assert that the number of results is the same.
    $results_after = count($page->findAll('css', '#project-browser .pb-project.list'));
    $this->assertEquals($results_before, $results_after);

    // Switch back to first tab.
    $this->pressWithWait('project_browser_test_mock');
    // Filter by search text.
    $this->inputSearchField('Number', TRUE);
    $assert_session->waitForElementVisible('css', ".search__search-submit")->click();
    $this->assertTrue($assert_session->waitForText('2 Results'));
    $this->assertProjectsVisible([
      '9 Starts With a Higher Number',
      '1 Starts With a Number',
    ]);
    // Again switch to second tab.
    $this->pressWithWait('random_data');
    $this->pressWithWait('Clear filters');
    // Switch back to first tab.
    $this->pressWithWait('project_browser_test_mock');
    $this->svelteInitHelper('css', '#project-browser .pb-project');
    // Assert that the filters persist.
    $this->assertTrue($assert_session->waitForText('2 Results'));
    $this->assertProjectsVisible([
      '9 Starts With a Higher Number',
      '1 Starts With a Number',
    ]);
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
      $this->drupalGet('admin/modules/browse/project_browser_test_mock');
      $this->svelteInitHelper('css', $selector['selector']);
      $this->getSession()->getPage()->pressButton($selector['value']);
      $this->svelteInitHelper('text', 'Helvetica');
      $assert_session->waitForButton('Helvetica')?->click();
      $this->svelteInitHelper('text', 'Close');
      $assert_session->waitForButton('Close')?->click();
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

    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $local_tasks = $assert_session->elementExists('css', 'h2:contains("Primary tabs") + ul')
      ->findAll('css', 'li a[href*="/admin/modules/browse/"]');
    $this->assertCount(2, $local_tasks);
    // Verify that the mock plugin is first tab.
    $this->assertSame('Browse', $local_tasks[0]->getText());

    // Re-order plugins.
    $this->drupalGet('admin/config/development/project_browser');
    $first_plugin = $page->find('css', '#source--project_browser_test_mock');
    $second_plugin = $page->find('css', '#source--random_data');
    $first_plugin->find('css', '.handle')->dragTo($second_plugin);
    $this->assertTableRowWasDragged($first_plugin);
    $this->submitForm([], 'Save');

    // Verify that Random data is first tab.
    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->assertSame('Random data', $local_tasks[0]->getText());

    // Disable the mock plugin.
    $this->drupalGet('admin/config/development/project_browser');
    $enabled_row = $page->find('css', '#source--project_browser_test_mock');
    $disabled_region_row = $page->find('css', '.status-title-disabled');
    $enabled_row->find('css', '.handle')->dragTo($disabled_region_row);
    $this->assertTableRowWasDragged($enabled_row);
    $this->submitForm([], 'Save');
    $assert_session->pageTextContains('The configuration options have been saved.');

    // Verify that only Random data plugin is enabled.
    $this->drupalGet('admin/modules/browse/random_data');
    $this->svelteInitHelper('css', '.pb-filter__checkbox');
    $assert_session->elementsCount('css', '.pb-filter__checkbox', 20);

    $this->config('project_browser.admin_settings')->set('enabled_sources', ['project_browser_test_mock'])->save(TRUE);
    $this->drupalGet('admin/config/development/project_browser');
    $this->assertTrue($assert_session->optionExists('edit-enabled-sources-project-browser-test-mock-status', 'enabled')->isSelected());
    $this->assertTrue($assert_session->optionExists('edit-enabled-sources-random-data-status', 'disabled')->isSelected());

    // Verify that only the mock plugin is enabled.
    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('css', '.pb-filter__checkbox');
    $assert_session->elementsCount('css', '.pb-filter__checkbox', 19);
  }

  /**
   * Tests the visibility of categories in list and grid view.
   */
  public function testCategoriesVisibility(): void {
    $assert_session = $this->assertSession();
    $view_options = [
      [
        'selector' => '.pb-display__button[value="Grid"]',
        'value' => 'Grid',
      ], [
        'selector' => '.pb-display__button[value="List"]',
        'value' => 'List',
      ],
    ];
    $this->getSession()->resizeWindow(1300, 1300);

    // Check visibility of categories in each view.
    foreach ($view_options as $selector) {
      $this->drupalGet('admin/modules/browse/project_browser_test_mock');
      $this->svelteInitHelper('css', $selector['selector']);
      $this->getSession()->getPage()->pressButton($selector['value']);
      $this->svelteInitHelper('text', 'Helvetica');
      $assert_session->elementsCount('css', '#project-browser .pb-layout__main ul li:nth-child(7) .pb-project-categories ul li', 1);
      $grid_text = $this->getElementText('#project-browser .pb-layout__main ul li:nth-child(7) .pb-project-categories ul li:nth-child(1)');
      $this->assertEquals('E-commerce', $grid_text);
      $assert_session->elementsCount('css', '#project-browser .pb-layout__main  ul li:nth-child(10) .pb-project-categories ul li', 2);
      $grid_text = $this->getElementText('#project-browser .pb-layout__main ul li:nth-child(7) .pb-project-categories ul li:nth-child(1)');
      $this->assertEquals('E-commerce', $grid_text);
      $grid_text = $this->getElementText('#project-browser .pb-layout__main ul li:nth-child(10) .pb-project-categories ul li:nth-child(2)');
      $this->assertEquals('E-commerce', $grid_text);
    }
  }

  /**
   * Tests the pagination and filtering.
   */
  public function testPaginationWithFilters(): void {
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->pressWithWait('Clear filters');
    $this->assertProjectsVisible([
      'Jazz',
      'Eggman',
      'Tooth Fairy',
      'Vitamin&C;$?',
      'Cream cheese on a bagel',
      'Pinky and the Brain',
      'Ice Ice',
      'No Scrubs',
      'Soup',
      'Mad About You',
      'Dancing Queen',
      'Kangaroo',
    ]);

    $this->assertPagerItems(['1', '2', '3', 'Next', 'Last']);
    $this->clickWithWait('[aria-label="Last page"]');
    $this->assertProjectsVisible([
      'Astronaut Simulator',
    ]);

    // Open category drop-down.
    $this->clickWithWait('.pb-filter__multi-dropdown', 'E-commerce', TRUE);

    // Click 'Media' checkbox.
    $this->clickWithWait('#67');
    $this->assertPagerItems(['1', '2', 'Next', 'Last']);
    $assert_session->elementExists('css', '.pager__item--active > .is-active[aria-label="Page 1"]');
  }

  /**
   * Tests install button link.
   */
  public function testInstallButtonLink(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->config('project_browser.admin_settings')
      ->set('enabled_sources', ['drupal_core'])
      ->save(TRUE);
    $this->drupalGet('admin/modules/browse/drupal_core');
    $this->svelteInitHelper('css', '.pb-project.pb-project--list');

    $this->inputSearchField('inline form errors', TRUE);
    $assert_session->waitForElementVisible('css', ".search__search-submit")->click();
    $this->svelteInitHelper('text', 'Inline Form Errors');

    $install_link = $page->find('css', '.pb-layout__main .pb-actions a');

    $this->assertStringContainsString('admin/modules#module-inline-form-errors', $install_link->getAttribute('href'));
    $this->drupalGet($install_link->getAttribute('href'));
    $assert_session->waitForElementVisible('css', "#edit-modules-inline-form-errors-enable");
    $assert_session->assertVisibleInViewport('css', '#edit-modules-inline-form-errors-enable');
  }

  /**
   * Confirms UI install can not be enabled without Package Manager installed.
   */
  public function testUiInstallNeedsPackageManager() {
    $this->drupalGet('admin/config/development/project_browser');
    $ui_install_input = $this->getSession()->getPage()->find('css', '[data-drupal-selector="edit-allow-ui-install"]');
    $this->assertTrue($ui_install_input->getAttribute('disabled') === 'disabled');

    // @todo Remove try/catch in https://www.drupal.org/i/3349193.
    try {
      $this->container->get('module_installer')->install(['package_manager'], TRUE);
    }
    catch (MissingDependencyException $e) {
      $this->markTestSkipped($e->getMessage());
    }
    $this->drupalGet('admin/config/development/project_browser');
    $ui_install_input = $this->getSession()->getPage()->find('css', '[data-drupal-selector="edit-allow-ui-install"]');
    $this->assertFalse($ui_install_input->hasAttribute('disabled'));
  }

  /**
   * Tests that we can clear search results with one click.
   */
  public function testClearKeywordSearch() {
    $assert_session = $this->assertSession();
    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('css', '.pb-search-results');

    // Get the original result count.
    $results = $assert_session->elementExists('css', '.pb-search-results');
    $this->assertTrue($results->waitFor(10, fn (NodeElement $element) => str_contains($element->getText(), 'Results')));
    $original_text = $results->getText();

    // Search for something to change it.
    $this->inputSearchField('abcdefghijklmnop', TRUE);
    $assert_session->waitForElementVisible('css', ".search__search-submit")->click();
    $this->assertTrue($results->waitFor(10, fn (NodeElement $element) => $element->getText() !== $original_text));

    // Remove the search text and make sure it auto-updates.
    // Use our clear search button to do it.
    $assert_session->elementExists('css', '.search__search-clear')->click();
    $this->assertTrue($results->waitFor(10, fn (NodeElement $element) => $element->getText() === $original_text));
  }

  /**
   * Test that the clear search link is not in the tab-index.
   *
   * @see https://www.drupal.org/project/project_browser/issues/3446109
   */
  public function testSearchClearNoTabIndex(): void {
    $page = $this->getSession()->getPage();
    $this->assertSession();
    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('css', '.pb-search-results');

    // Search and confirm clear button has no focus after tabbing.
    $this->inputSearchField('abcdefghijklmnop', TRUE);
    $this->assertSession()->waitForElementVisible('css', ".search__search-submit")->click();

    $this->getSession()->getDriver()->keyPress($page->getXpath(), '9');
    $has_focus_id = $this->getSession()->evaluateScript('document.activeElement.id');
    $this->assertNotEquals('clear-text', $has_focus_id);
  }

  /**
   * Tests that recipes show instructions for applying them.
   */
  public function testRecipeInstructions(): void {
    if (!class_exists(Recipe::class)) {
      $this->markTestSkipped('This test cannot run because this version of Drupal does not support recipes.');
    }
    $assert_session = $this->assertSession();

    $this->config('project_browser.admin_settings')
      ->set('enabled_sources', ['recipes'])
      ->save();

    $this->drupalGet('admin/modules/browse/recipes');
    $this->svelteInitHelper('css', '.pb-projects-list');
    $this->inputSearchField('image', TRUE);
    $assert_session->waitForElementVisible('css', ".search__search-submit")->click();

    // Look for a recipe that ships with core.
    $card = $assert_session->waitForElementVisible('css', '.pb-project:contains("Image media type")');
    $this->assertNotEmpty($card);
    $assert_session->buttonExists('View Commands', $card)->press();
    $input = $assert_session->waitForElementVisible('css', '.command-box textarea');
    $this->assertNotEmpty($input);
    $command = $input->getValue();
    // A full path to the PHP executable should be in the command.
    $this->assertMatchesRegularExpression('/[^\s]+\/php /', $command);
    $drupal_root = $this->getDrupalRoot();
    $this->assertStringStartsWith("cd $drupal_root\n", $command);
    $this->assertStringEndsWith("php $drupal_root/core/scripts/drupal recipe $drupal_root/core/recipes/image_media_type", $command);
  }

  /**
   * Test that items with 0 active installs don't show, and >0 do.
   */
  public function testActiveInstallVisibility(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('css', '.pb-search-results');

    $assert_session->waitForElementVisible('css', '.pb-project');
    // Find the first and last .pb-project elements.
    $projects = $page->findAll('css', '.pb-project');

    // Assert that there are pb-project elements on the page.
    $this->assertNotEmpty($projects, 'No .pb-project elements found on the page.');

    // Check the first project DOES contain the
    // pb-project__install-count_container div.
    $first_project = reset($projects);
    $first_install_count_container = $first_project->find('css', '.pb-project__install-count-container');
    $this->assertNotNull($first_install_count_container, 'First project does not contain the install count container.');

    // Check the last project does NOT contain the
    // pb-project__install-count_container div.
    $last_project = end($projects);
    $last_install_count_container = $last_project->find('css', '.pb-project__install-count-container');
    $this->assertNull($last_install_count_container, 'Last project contains the install count container, but it should not.');
  }

  /**
   * Tests that each source plugin has its own dedicated route.
   */
  public function testSourcePluginRoutes(): void {
    // Enable module for extra source plugin.
    $this->container->get('module_installer')->install(['project_browser_devel'], TRUE);
    $this->rebuildContainer();

    $current_sources = $this->container->get(EnabledSourceHandler::class)->getCurrentSources();
    $this->assertCount(2, $current_sources);

    foreach (array_keys($current_sources) as $plugin_id) {
      $this->drupalGet("/admin/modules/browse/{$plugin_id}");
      $this->assertNotNull($this->assertSession()->waitForElementVisible('css', '#project-browser .pb-project.pb-project--list'));
    }
  }

  /**
   * Verifies that the wrench icon is displayed only on maintained projects.
   */
  public function testWrenchIcon(): void {
    $assert_session = $this->assertSession();
    $this->getSession()->resizeWindow(1460, 960);
    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('text', 'Helvetica');
    // This asserts that status icon is present on the cards.
    $this->assertNotNull($assert_session->waitForElementVisible('css', '.pb-project__maintenance-icon .pb-project__status-icon-btn'));
    $assert_session->waitForButton('Helvetica')?->click();
    $this->assertTrue($assert_session->waitForText('The module is actively maintained by the maintainers'));
    // This asserts that status icon is present in detail's modal.
    $this->assertNotNull($assert_session->waitForElementVisible('css', '.pb-detail-modal__sidebar .pb-project__status-icon-btn'));
    $this->getSession()->getPage()->find('css', '.ui-dialog-titlebar-close')->click();

    $this->clickWithWait(self::MAINTENANCE_OPTION_SELECTOR . self::OPTION_LAST_CHILD);
    $this->assertEquals('Show all', $this->getElementText(self::MAINTENANCE_OPTION_SELECTOR . self::OPTION_LAST_CHILD));
    // Asserts that the text followed by status icon is missing.
    $assert_session->waitForButton('Eggman')?->click();
    $this->assertFalse($assert_session->waitForText('The module is actively maintained by the maintainers'));
  }

  /**
   * Tests that count of installs is formatted for plurals correctly.
   */
  public function testInstallCountPluralFormatting(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('admin/modules/browse/project_browser_test_mock');

    // Ensure the project list is loaded.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#project-browser .pb-project'));
    $this->assertTrue($assert_session->waitForText('Results'));

    // Expect Grapefruit to have 1 install.
    $assert_session->waitForElementVisible('xpath', '//span[contains(@class, "pb-project__install-count") and text()="1 install"]');

    // Locate and click the Grapefruit project link.
    $grapefruit_link = $page->find('xpath', '//button[contains(@class, "pb-project__link") and contains(text(), "Grapefruit")]');
    $grapefruit_link->click();

    // Verify the text for Grapefruit (singular case).
    $this->assertTrue($assert_session->waitForText('site reports using this module'));

    // Go back to the project list.
    $close_button = $page->find('xpath', '//button[contains(@class, "ui-dialog-titlebar-close") and contains(text(), "Close")]');
    $close_button->click();

    // Expect Octopus to have 235 installs.
    $assert_session->elementExists('xpath', '//span[contains(@class, "pb-project__install-count") and text()="235 installs"]');

    // Locate and click the Octopus project link.
    $octopus_link = $page->find('xpath', '//button[contains(@class, "pb-project__link") and contains(text(), "Octopus")]');
    $octopus_link->click();

    // Verify the text for Octopus (plural case).
    $this->assertTrue($assert_session->waitForText('sites report using this module'));
  }

  /**
   * Tests that pressing Enter in the search box doesn't reload the page.
   */
  public function testEnterDoesNotReloadThePage(): void {
    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $assert_session = $this->assertSession();
    $search_box = $assert_session->waitForElementVisible('css', '#pb-text');
    $this->assertNotEmpty($search_box);
    $session = $this->getSession();
    $session->executeScript('document.body.classList.add("same-page")');
    // Enter some nonsense in the search box and press Enter ("\r\n" in PHP).
    $search_box->focus();
    $search_box->setValue("foo\r\n");
    // The window should not have been reloaded, so the body should still have
    // the class we set.
    $assert_session->elementAttributeContains('css', 'body', 'class', 'same-page');
  }

  /**
   * Tests the singular and plural formatting for search results.
   */
  public function testSingularAndPluralResults(): void {
    // Load the Project Browser mock page.
    $this->drupalGet('admin/modules/browse/project_browser_test_mock');

    // Check for plural results initially.
    $this->svelteInitHelper('text', '10 Results');

    // Locate the search box and verify it is visible.
    $assert_session = $this->assertSession();
    $search_box = $assert_session->waitForElementVisible('css', '#pb-text');
    $this->assertNotEmpty($search_box, 'Search box is visible.');

    // Fill in the search field.
    $this->inputSearchField('', TRUE);
    // Set the search term to "Astronaut Simulator" to narrow the results.
    $this->inputSearchField('Astronaut Simulator', TRUE);
    $assert_session->waitForElementVisible('css', ".search__search-submit")?->click();

    // Verify the singular result count text is displayed correctly.
    $result_count_text = $assert_session->waitForElementVisible('css', '.pb-search-results')?->getText();
    $this->assertSame('1 Result', $result_count_text);
  }

}
