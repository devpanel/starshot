<?php

declare(strict_types=1);

namespace Drupal\Tests\project_browser\Kernel;

use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Recipe\RecipeRunner;
use Drupal\Core\State\StateInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\project_browser\ActivationStatus;
use Drupal\project_browser\ActivatorInterface;
use Drupal\project_browser\ProjectBrowser\Project;
use Drupal\project_browser\ProjectType;

/**
 * Tests the recipe activator. Obviously.
 *
 * @group project_browser
 * @covers \Drupal\project_browser\RecipeActivator
 */
class RecipeActivatorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['project_browser'];

  /**
   * Tests that Project Browser stores fully resolved paths of applied recipes.
   */
  public function testAbsoluteRecipePathIsStoredOnApply(): void {
    $base_dir = $this->getDrupalRoot() . '/core/tests/fixtures/recipes';
    if (!is_dir($base_dir)) {
      $this->markTestSkipped('This test requires a version of Drupal that supports recipes.');
    }
    $recipe = Recipe::createFromDirectory($base_dir . '/invalid_config/../no_extensions');
    RecipeRunner::processRecipe($recipe);

    $applied_recipes = $this->container->get(StateInterface::class)
      ->get('project_browser.applied_recipes', []);
    $this->assertContains($base_dir . '/no_extensions', $applied_recipes);
  }

  /**
   * Tests recipe activation with a project which is not installed physically.
   */
  public function testGetStatus(): void {
    $project = new Project(
      logo: [],
      isCompatible: TRUE,
      machineName: 'My Project',
      body: [],
      title: '',
      author: [],
      packageName: 'My Project',
      type: ProjectType::Recipe,
    );
    $activator = $this->container->get(ActivatorInterface::class);
    // As this project is not installed the RecipeActivator::getPath() will
    // return NULL in RecipeActivator::getStatus() and it will return the
    // status as Absent.
    // @see \Drupal\project_browser\RecipeActivator::getStatus()
    $this->assertSame(ActivationStatus::Absent, $activator->getStatus($project));
  }

}
