<?php

declare(strict_types=1);

namespace Drupal\Tests\project_browser\Kernel;

use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\project_browser\EnabledSourceHandler;
use Drupal\project_browser\ProjectBrowser\Project;

/**
 * @covers \Drupal\project_browser\EnabledSourceHandler
 * @group project_browser
 */
class EnabledSourceHandlerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'project_browser',
    'project_browser_test',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('project_browser_test', [
      'project_browser_projects',
      'project_browser_categories',
    ]);
    $this->installConfig('project_browser_test');
    $this->config('project_browser.admin_settings')->set('enabled_sources', ['project_browser_test_mock'])->save(TRUE);

    $this->container->get(ModuleInstallerInterface::class)->install([
      'project_browser_test',
      'project_browser',
    ]);

    $this->container->get('module_handler')->loadInclude('project_browser_test', 'install');
    // Hooks are not ran on kernel tests, so trigger it.
    project_browser_test_install();
  }

  /**
   * Tests that trying to load a previously unseen project throws an exception.
   */
  public function testExceptionOnGetUnknownProject(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage("Project 'unseen' was not found in non-volatile storage.");

    $this->container->get(EnabledSourceHandler::class)
      ->getStoredProject('unseen');
  }

  /**
   * Tests loading a previously seen project.
   */
  public function testGetStoredProject(): void {
    $handler = $this->container->get(EnabledSourceHandler::class);

    $projects = $handler->getProjects('project_browser_test_mock');
    $list = reset($projects)->list;
    $this->assertNotEmpty($list);
    $project = reset($list);

    $project_again = $handler->getStoredProject($project->id);
    $this->assertNotSame($project, $project_again);
    $this->assertSame($project->jsonSerialize(), $project_again->jsonSerialize());

    // The activation status and commands should be set.
    $this->assertTrue(self::hasActivationData($project_again));
  }

  /**
   * Tests that projects are not stored with any activation data.
   */
  public function testProjectsAreStoredWithoutActivationData(): void {
    // Projects returned from getProjects() should have their activation status
    // and commands set.
    $projects = $this->container->get(EnabledSourceHandler::class)
      ->getProjects('project_browser_test_mock');
    $list = reset($projects)->list;
    $this->assertNotEmpty($list);
    $project = reset($list);
    $this->assertTrue(self::hasActivationData($project));

    // But if we pull the project directly from the data store, the `status` and
    // `commands` properties should be uninitialized.
    $project = $this->container->get('keyvalue.expirable')
      ->get('project_browser')
      ->get($project->id);
    $this->assertInstanceOf(Project::class, $project);
    $this->assertFalse(self::hasActivationData($project));
  }

  /**
   * Checks if a project object is carrying activation data.
   *
   * @param \Drupal\project_browser\ProjectBrowser\Project $project
   *   The project object.
   *
   * @return bool
   *   TRUE if the project has its activation status and commands set, FALSE
   *   otherwise.
   */
  private static function hasActivationData(Project $project): bool {
    $status = new \ReflectionProperty(Project::class, 'status');
    $commands = new \ReflectionProperty(Project::class, 'commands');
    return $status->isInitialized($project) && $commands->isInitialized($project);
  }

}
