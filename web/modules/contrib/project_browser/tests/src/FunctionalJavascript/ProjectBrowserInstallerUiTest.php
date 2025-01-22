<?php

declare(strict_types=1);

namespace Drupal\Tests\project_browser\FunctionalJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\Core\Recipe\Recipe;
use Drupal\Core\State\StateInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\project_browser\EnabledSourceHandler;
use Drupal\project_browser\InstallState;
use Drupal\system\SystemManager;
use Drupal\Tests\project_browser\Traits\PackageManagerFixtureUtilityTrait;

/**
 * Provides tests for the Project Browser Installer UI.
 *
 * @coversDefaultClass \Drupal\project_browser\Controller\InstallerController
 *
 * @group project_browser
 */
class ProjectBrowserInstallerUiTest extends WebDriverTestBase {

  use ProjectBrowserUiTestTrait, PackageManagerFixtureUtilityTrait;

  /**
   * The install state service.
   *
   * @var \Drupal\project_browser\InstallState
   */
  private InstallState $installState;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'package_manager_bypass',
    'package_manager',
    'package_manager_test_validation',
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

    $this->initPackageManager();

    $this->installState = $this->container->get(InstallState::class);

    $this->config('project_browser.admin_settings')
      ->set('enabled_sources', ['project_browser_test_mock'])
      ->set('allow_ui_install', TRUE)
      ->set('max_selections', 1)
      ->save();
    $this->drupalLogin($this->drupalCreateUser([
      'administer modules',
      'administer site configuration',
    ]));
  }

  /**
   * Tests the "select" button functionality.
   */
  public function testSingleModuleAddAndInstall(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('text', 'Cream cheese on a bagel');
    $cream_cheese_module_selector = '#project-browser .pb-layout__main ul > li:nth-child(1)';
    $download_button = $assert_session->waitForElementVisible('css', "$cream_cheese_module_selector button.project__action_button");
    $this->assertNotEmpty($download_button);
    $this->assertSame('Install Cream cheese on a bagel', $download_button->getText());
    $download_button->click();
    $installed_action = $assert_session->waitForElementVisible('css', "$cream_cheese_module_selector .project_status-indicator", 30000);
    $this->assertTrue($assert_session->waitForText('Cream cheese on a bagel is Installed'));
    $this->assertSame('Cream cheese on a bagel is Installed', $installed_action->getText());

    // The activator in project_browser_test should have logged a message.
    // @see \Drupal\project_browser_test\TestActivator
    $this->assertContains('Cream cheese on a bagel was activated!', $this->container->get(StateInterface::class)->get('test activator'));
  }

  /**
   * Tests already added project install functionality.
   *
   * This scenario is not possible if only the Project
   * Browser UI is used, but could happen if the module was added differently,
   * such as via the terminal with Compose or a direct file addition.
   */
  public function testInstallModuleAlreadyInFilesystem() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('text', 'Pinky and the Brain');
    $pinky_brain_selector = '#project-browser .pb-layout__main ul > li:nth-child(2)';
    $action_button = $assert_session->waitForElementVisible('css', "$pinky_brain_selector button.project__action_button");
    $this->assertNotEmpty($action_button);
    $this->assertSame('Install Pinky and the Brain', $action_button->getText());
    $action_button->click();
    $popup = $assert_session->waitForElementVisible('css', '.project-browser-popup');
    $this->assertNotEmpty($popup);
    // The Pinky and the Brain module doesn't actually exist in the filesystem,
    // but the test activator pretends it does, in order to test the presence
    // of the "Install" button as opposed vs. the default "Add and Install"
    // button. This happens to be a good way to test mid-install exceptions as
    // well.
    // @see \Drupal\project_browser_test\TestActivator::getStatus()
    $this->assertStringContainsString('MissingDependencyException: Unable to install modules pinky_brain due to missing modules pinky_brain', $popup->getText());
  }

  /**
   * Tests applying a recipe from the project browser UI.
   */
  public function testApplyRecipe(): void {
    if (!class_exists(Recipe::class)) {
      $this->markTestSkipped('This test cannot run because this version of Drupal does not support recipes.');
    }
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->config('project_browser.admin_settings')
      ->set('enabled_sources', ['recipes'])
      ->save();

    $this->drupalGet('admin/modules/browse/recipes');
    $this->svelteInitHelper('css', '.pb-projects-list');
    $this->inputSearchField('image', TRUE);
    $assert_session->waitForElementVisible('css', ".search__search-submit")->click();

    $assert_installed = function (NodeElement $card): void {
      $installed = $card->waitFor(30, function () use ($card): bool {
        return $card->has('css', '.project_status-indicator:contains("Installed")');
      });
      $this->assertTrue($installed);
    };

    // Apply a recipe that ships with core.
    $card = $assert_session->waitForElementVisible('css', '.pb-project:contains("Image media type")');
    $this->assertNotEmpty($card);
    $assert_session->buttonExists('Install', $card)->press();
    $assert_installed($card);

    // If we reload, the installation status should be remembered.
    $this->getSession()->reload();
    $card = $assert_session->waitForElementVisible('css', '.pb-project:contains("Image media type")');
    $this->assertNotEmpty($card);
    $assert_installed($card);

    // Apply a recipe that requires user input.
    // @todo Remove this check in https://www.drupal.org/i/3494848.
    if (!property_exists(Recipe::class, 'input')) {
      $this->markTestSkipped('This test cannot continue because this version of Drupal does not support collecting recipe input.');
    }
    $this->inputSearchField('test', TRUE);
    $assert_session->waitForElementVisible('css', ".search__search-submit")->click();
    $card = $assert_session->waitForElementVisible('css', '.pb-project:contains("Test Recipe")');
    $this->assertNotEmpty($card);
    $assert_session->buttonExists('Install', $card)->press();
    $field = $assert_session->waitForField('test_recipe[new_name]');
    $this->assertNotEmpty($field);
    $field->setValue('Y halo thar!');
    $page->pressButton('Continue');
    $this->checkForMetaRefresh();
    $this->inputSearchField('test', TRUE);
    $assert_session->waitForElementVisible('css', ".search__search-submit")->click();
    $card = $assert_session->waitForElementVisible('css', '.pb-project:contains("Test Recipe")');
    $assert_installed($card);
    $this->assertSame('Y halo thar!', $this->config('system.site')->get('name'));
  }

  /**
   * Tests install UI not available if not enabled.
   */
  public function testAllowUiInstall(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('text', 'Pinky and the Brain');

    $cream_cheese_module_selector = '#project-browser .pb-layout__main ul > li:nth-child(1)';
    $download_button = $assert_session->waitForElementVisible('css', "$cream_cheese_module_selector button.project__action_button");
    $this->assertNotEmpty($download_button);
    $this->assertSame('Install Cream cheese on a bagel', $download_button->getText());
    $this->drupalGet('/admin/config/development/project_browser');
    $page->find('css', '#edit-allow-ui-install')->click();
    $assert_session->checkboxNotChecked('edit-allow-ui-install');
    $this->submitForm([], 'Save');
    $this->assertTrue($assert_session->waitForText('The configuration options have been saved.'));

    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('text', 'Cream cheese on a bagel');
    $action_button = $assert_session->waitForElementVisible('css', "$cream_cheese_module_selector button.project__action_button");
    $this->assertNotEmpty($action_button);
    $this->assertSame('View Commands for Cream cheese on a bagel', $action_button->getText());
  }

  /**
   * Confirms stage can be unlocked despite a missing Project Browser lock.
   *
   * @covers ::unlock
   */
  public function testCanBreakStageWithMissingProjectBrowserLock() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Start install begin.
    $this->drupalGet('admin/modules/project_browser/install-begin', [
      'query' => ['source' => 'project_browser_test_mock'],
    ]);
    $this->installState->deleteAll();
    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('text', 'Cream cheese on a bagel');
    // Try beginning another install while one is in progress, but not yet in
    // the applying stage.
    $cream_cheese_module_selector = '#project-browser .pb-layout__main ul > li:nth-child(1)';
    $cream_cheese_button = $page->find('css', "$cream_cheese_module_selector button.project__action_button");
    $cream_cheese_button->click();

    $this->assertTrue($assert_session->waitForText('The process for adding projects is locked, but that lock has expired. Use unlock link to unlock the process and try to add the project again.'));

    // Click Unlock Install Stage link.
    $this->clickWithWait('#ui-id-1 > p > a');
    $this->svelteInitHelper('text', 'Cream cheese on a bagel');
    // Try beginning another install after breaking lock.
    $cream_cheese_button = $page->find('css', "$cream_cheese_module_selector button.project__action_button");
    $cream_cheese_button->click();
    $installed_action = $assert_session->waitForElementVisible('css', "$cream_cheese_module_selector .project_status-indicator", 30000);
    $assert_session->waitForText('Cream cheese on a bagel is Installed');
    $this->assertSame('Cream cheese on a bagel is Installed', $installed_action->getText());

  }

  /**
   * Confirms the break lock link is available and works.
   *
   * The break lock link is not available once the stage is applying.
   *
   * @covers ::unlock
   */
  public function testCanBreakLock() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Find a project we can install.
    $project_id = $this->chooseProjectToInstall(['cream_cheese']);

    // Start install begin.
    $this->drupalGet('admin/modules/project_browser/install-begin', [
      'query' => ['source' => 'project_browser_test_mock'],
    ]);
    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('text', 'Cream cheese on a bagel');
    // Try beginning another install while one is in progress, but not yet in
    // the applying stage.
    $cream_cheese_module_selector = '#project-browser .pb-layout__main ul > li:nth-child(1)';
    $cream_cheese_button = $page->find('css', "$cream_cheese_module_selector button.project__action_button");
    $cream_cheese_button->click();
    $this->assertTrue($assert_session->waitForText('The process for adding projects is locked, but that lock has expired. Use unlock link to unlock the process and try to add the project again.'));
    // Click Unlock Install Stage link.
    $this->clickWithWait('#ui-id-1 > p > a');
    $this->svelteInitHelper('text', 'Cream cheese on a bagel');
    // Try beginning another install after breaking lock.
    $cream_cheese_button = $page->find('css', "$cream_cheese_module_selector button.project__action_button");
    $cream_cheese_button->click();
    $installed_action = $assert_session->waitForElementVisible('css', "$cream_cheese_module_selector .project_status-indicator", 30000);
    $assert_session->waitForText('Cream cheese on a bagel is Installed');
    $this->assertSame('Cream cheese on a bagel is Installed', $installed_action->getText());
  }

  /**
   * Confirm that a status check error prevents download and install.
   */
  public function testPackageManagerErrorPreventsDownload(): void {
    // @see \Drupal\project_browser_test\TestInstallReadiness
    $this->container->get(StateInterface::class)
      ->set('project_browser_test.simulated_result_severity', SystemManager::REQUIREMENT_ERROR);

    $assert_session = $this->assertSession();
    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $settings = $this->getDrupalSettings();
    $this->assertTrue($settings['project_browser']['package_manager']['status_checked']);
    $this->svelteInitHelper('text', 'Cream cheese on a bagel');
    $assert_session->statusMessageContains("Simulate an error message for the project browser.", 'error');
    $cream_cheese_module_selector = '#project-browser .pb-layout__main ul > li:nth-child(1)';
    $download_button_text = $assert_session->waitForElementVisible('css', "$cream_cheese_module_selector button.project__action_button")
      ?->getText();
    $this->assertSame('View Commands for Cream cheese on a bagel', $download_button_text);
  }

  /**
   * Confirm that a status check warning allows download and install.
   */
  public function testPackageManagerWarningAllowsDownloadInstall(): void {
    // @see \Drupal\project_browser_test\TestInstallReadiness
    $this->container->get(StateInterface::class)
      ->set('project_browser_test.simulated_result_severity', SystemManager::REQUIREMENT_WARNING);

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('text', 'Cream cheese on a bagel');
    $assert_session->statusMessageContains("Simulate a warning message for the project browser.", 'warning');
    $cream_cheese_module_selector = '#project-browser .pb-layout__main ul > li:nth-child(1)';
    $download_button = $assert_session->waitForElementVisible('css', "$cream_cheese_module_selector button.project__action_button");
    $this->assertNotEmpty($download_button);
    $this->assertSame('Install Cream cheese on a bagel', $download_button->getText());
    $download_button->click();
    $installed_action = $assert_session->waitForElementVisible('css', "$cream_cheese_module_selector .project_status-indicator", 30000);
    $this->assertNotEmpty($installed_action);
    $installed_action = $installed_action->waitFor(30, function ($button) {
      return $button->getText() === 'Cream cheese on a bagel is Installed';
    });
    $this->assertTrue($installed_action);
  }

  /**
   * Finds a project from the provided source that can be installed.
   *
   * @param string[] $except_these_machine_names
   *   Project machine names that should be ignored.
   * @param string $source_id
   *   The ID of the source to query for projects.
   *
   * @return string
   *   The project ID to use.
   */
  private function chooseProjectToInstall(array $except_these_machine_names = [], string $source_id = 'project_browser_test_mock'): string {
    $handler = $this->container->get(EnabledSourceHandler::class);
    $projects = $handler->getProjects($source_id);
    $results = $projects[$source_id];

    foreach ($results->list as $project) {
      if (in_array($project->machineName, $except_these_machine_names, TRUE)) {
        continue;
      }
      $source = $handler->getCurrentSources()[$source_id] ?? NULL;
      if ($source && method_exists($source, 'isProjectSafe') && !$source->isProjectSafe($project)) {
        continue;
      }
      return $project->id;
    }

    $this->fail("Could not find a project to install from amongst the enabled sources.");
  }

  /**
   * Tests the "Install selected projects" button functionality.
   */
  public function testMultipleModuleAddAndInstall(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->drupalGet('project-browser/project_browser_test_mock');
    $this->svelteInitHelper('text', 'Cream cheese on a bagel');
    $assert_session->buttonNotExists('Install selected projects');

    $cream_cheese_module_selector = '#project-browser .pb-layout__main ul > li:nth-child(1)';
    $select_button1 = $assert_session->waitForElementVisible('css', "$cream_cheese_module_selector button.project__action_button");
    $this->assertNotEmpty($select_button1);
    $this->assertSame('Select Cream cheese on a bagel', $select_button1->getText());
    $select_button1->click();
    $was_selected = $select_button1->waitFor(10, fn ($button) => $button->getText() === 'Deselect Cream cheese on a bagel');
    $this->assertTrue($was_selected);

    $dancing_queen_button = $page->find('css', '#project-browser .pb-layout__main ul > li:nth-child(3) button');
    $this->assertFalse($dancing_queen_button->hasAttribute('disabled'));

    $this->assertNotEmpty($assert_session->waitForButton('Install selected projects'));

    $kangaroo_module_selector = '#project-browser .pb-layout__main ul > li:nth-child(4)';
    $select_button2 = $assert_session->waitForElementVisible('css', "$kangaroo_module_selector button.project__action_button");
    $this->assertNotEmpty($select_button2);
    $this->assertSame('Select Kangaroo', $select_button2->getText());
    $select_button2->click();
    $was_deselected = $select_button2->waitFor(10, function ($button) {
      return $button->getText() === 'Deselect Kangaroo';
    });
    $this->assertTrue($was_deselected);
    // Select button gets disabled on reaching maximum limit.
    $assert_session->elementAttributeExists('css', '#project-browser .pb-layout__main ul > li:nth-child(3) button.project__action_button', 'disabled');

    $this->assertNotEmpty($assert_session->waitForButton('Install selected projects'));
    $page->pressButton('Install selected projects');

    $installed_action = $assert_session->waitForElementVisible('css', "$cream_cheese_module_selector .project_status-indicator", 30000);
    $installed_action = $installed_action->waitFor(30, function ($button) {
      return $button->getText() === 'Cream cheese on a bagel is Installed';
    });
    $this->assertTrue($installed_action);

    $installed_action = $assert_session->waitForElementVisible('css', "$kangaroo_module_selector .project_status-indicator", 30000);
    $installed_action = $installed_action->waitFor(30, function ($button) {
      return $button->getText() === 'Kangaroo is Installed';
    });
    $this->assertTrue($installed_action);

    // The activator in project_browser_test should have logged a message.
    // @see \Drupal\project_browser_test\TestActivator
    $this->assertContains('Cream cheese on a bagel was activated!', $this->container->get(StateInterface::class)->get('test activator'));
    $this->assertContains('Kangaroo was activated!', $this->container->get(StateInterface::class)->get('test activator'));
  }

  /**
   * Tests that adding projects to queue is plugin specific.
   */
  public function testPluginSpecificQueue() {
    $assert_session = $this->assertSession();
    $this->container->get('module_installer')->install(['project_browser_devel'], TRUE);
    $this->drupalGet('project-browser/project_browser_test_mock');

    $assert_session->buttonNotExists('Install selected projects');
    $cream_cheese_module_selector = '#project-browser .pb-layout__main ul > li:nth-child(1)';
    $select_button1 = $assert_session->waitForElementVisible('css', "$cream_cheese_module_selector button.project__action_button");
    $select_button1->click();
    $this->assertNotEmpty($assert_session->waitForButton('Install selected projects'));

    $this->drupalGet('project-browser/random_data');
    $assert_session->buttonNotExists('Install selected projects');
    $random_data = '#project-browser .pb-layout__main ul > li:nth-child(2)';
    $select_button2 = $assert_session->waitForElementVisible('css', "$random_data button.project__action_button");
    $this->assertNotEmpty($select_button2);
    $select_button2->click();
    $this->assertNotEmpty($assert_session->waitForButton('Install selected projects'));
  }

  /**
   * Tests that unlock url has correct href.
   */
  public function testUnlockLinkMarkup(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('admin/modules/project_browser/install-begin', [
      'query' => ['source' => 'project_browser_test_mock'],
    ]);
    $this->installState->deleteAll();
    $this->drupalGet('admin/modules/browse/project_browser_test_mock');
    $this->svelteInitHelper('text', 'Cream cheese on a bagel');
    $cream_cheese_module_selector = '#project-browser .pb-layout__main ul > li:nth-child(1)';
    $download_button = $assert_session->waitForElementVisible('css', "$cream_cheese_module_selector button.project__action_button");
    $this->assertNotEmpty($download_button);
    $this->assertSame('Install Cream cheese on a bagel', $download_button->getText());
    $download_button->click();
    $unlock_url = $assert_session->waitForElementVisible('css', "#unlock-link")->getAttribute('href');
    $this->assertStringEndsWith('/admin/modules/project_browser/install/unlock', parse_url($unlock_url, PHP_URL_PATH));
    $query = parse_url($unlock_url, PHP_URL_QUERY);
    parse_str($query, $query);
    $this->assertNotEmpty($query['token']);
    $this->assertStringEndsWith('/admin/modules/browse/project_browser_test_mock', $query['destination']);
  }

  /**
   * Tests the "Select/Deselect" button functionality in modal.
   */
  public function testSelectDeselectToggleInModal(): void {
    $assert_session = $this->assertSession();
    $this->drupalGet('project-browser/project_browser_test_mock');
    $this->svelteInitHelper('text', 'Helvetica');
    $assert_session->waitForButton('Helvetica')?->click();
    // Click select button in modal.
    $assert_session->elementExists('css', '.pb-detail-modal__sidebar_element button.project__action_button')->click();

    $this->assertSame('Deselect Helvetica',
      $assert_session->elementExists('css', '.pb-detail-modal__sidebar_element button.project__action_button')->getText());

    // Close the modal.
    $assert_session->waitForButton('Close')?->click();
    $assert_session->elementNotExists('xpath', '//span[contains(@class, "ui-dialog-title") and text()="Helvetica"]');
    $select_button = $assert_session->waitForElementVisible('css', "#project-browser .pb-layout__main ul > li:nth-child(7) button.project__action_button");
    $this->assertNotEmpty($select_button);
    // Asserts that the project is selected.
    $was_selected = $select_button->waitFor(10, fn ($button) => $button->getText() === 'Deselect Helvetica');
    $this->assertTrue($was_selected);
  }

}
