<?php

namespace Drupal\Tests\project_browser\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\project_browser\Plugin\ProjectBrowserSourceManager;
use Drupal\project_browser\ProjectBrowser\Project;

/**
 * Tests 'Core (Experimental)' label change.
 *
 * @coversDefaultClass \Drupal\project_browser\Plugin\ProjectBrowserSource\DrupalCore
 *
 * @group project_browser
 */
class CoreExperimentalLabelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'project_browser',
  ];

  /**
   * Tests 'Core (Experimental)' label.
   *
   * This test will fail if the language for experimental modules has changed.
   * If it fails, we need to update `DrupalCore::projectIsCovered` to look for
   * the new language that indicates a module is experimental.
   *
   * @covers ::getProjectData
   */
  public function testCoreExperimentalLabel(): void {
    $plugin_instance = $this->container->get(ProjectBrowserSourceManager::class)
      ->createInstance('drupal_core');
    $modules_to_test = ['Experimental Test', 'System'];
    $filtered_projects = array_filter($plugin_instance->getProjects()->list, fn(Project $value) => in_array($value->title, $modules_to_test));
    $this->assertCount(2, $filtered_projects);
    foreach ($filtered_projects as $project) {
      if ($project->title === 'System') {
        $this->assertTrue($project->isCovered);
      }
      elseif ($project->title === 'Experimental Test') {
        $this->assertFalse($project->isCovered);
      }
    }
  }

}
