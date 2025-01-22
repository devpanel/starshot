<?php

declare(strict_types=1);

namespace Drupal\project_browser\Plugin\ProjectBrowserSource;

use Composer\InstalledVersions;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\project_browser\Plugin\ProjectBrowserSourceBase;
use Drupal\project_browser\ProjectBrowser\Project;
use Drupal\project_browser\ProjectBrowser\ProjectsResultsPage;
use Drupal\project_browser\ProjectType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;

/**
 * A source plugin that exposes recipes installed locally.
 */
class Recipes extends ProjectBrowserSourceBase {

  public function __construct(
    private readonly FileSystemInterface $fileSystem,
    private readonly CacheBackendInterface $cacheBin,
    private readonly ModuleExtensionList $moduleList,
    private readonly FileUrlGeneratorInterface $fileUrlGenerator,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly string $appRoot,
    mixed ...$arguments,
  ) {
    parent::__construct(...$arguments);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $container->get(FileSystemInterface::class),
      $container->get('cache.project_browser'),
      $container->get(ModuleExtensionList::class),
      $container->get(FileUrlGeneratorInterface::class),
      $container->get(ConfigFactoryInterface::class),
      $container->getParameter('app.root'),
      ...array_slice(func_get_args(), 1),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getProjects(array $query = []): ProjectsResultsPage {
    $cached = $this->cacheBin->get($this->getPluginId());
    if ($cached) {
      $projects = $cached->data;
    }
    else {
      $projects = [];

      $logo_url = $this->moduleList->getPath('project_browser') . '/images/recipe-logo.png';
      $logo_url = $this->fileUrlGenerator->generateString($logo_url);

      $finder = $this->getFinder();
      // If we're in a test environment, scan for our test recipes too, along
      // with any arbitrary places that might be specified in a setting.
      if (Settings::get('extension_discovery_scan_tests', FALSE) || drupal_valid_test_ua()) {
        $finder->in([
          ...Settings::get('project_browser_recipe_directories', []),
          __DIR__ . '/../../../tests/fixtures',
        ]);
      }
      /** @var \Symfony\Component\Finder\SplFileInfo $file */
      foreach ($finder as $file) {
        $path = $file->getPath();

        // If the recipe isn't part of Drupal core, get its package name from
        // `composer.json`. This shouldn't be necessary once drupal.org has a
        // proper API endpoint that provides project information for recipes.
        if (str_starts_with($path, $this->appRoot . '/core/recipes/')) {
          $package_name = 'drupal/core';
        }
        else {
          $package = file_get_contents($path . '/composer.json');
          $package = Json::decode($package);
          $package_name = $package['name'];

          if (array_key_exists('homepage', $package)) {
            $url = Url::fromUri($package['homepage']);
          }
        }

        $recipe = Yaml::decode($file->getContents());
        $description = $recipe['description'] ?? NULL;

        $projects[] = new Project(
          logo: [
            'file' => [
              'uri' => $logo_url,
              'resource' => 'image',
            ],
            'alt' => (string) $this->t('@name logo', [
              '@name' => $recipe['name'],
            ]),
          ],
          isCompatible: TRUE,
          machineName: basename($path),
          body: $description ? ['value' => $description] : [],
          title: $recipe['name'],
          author: [],
          packageName: $package_name,
          type: ProjectType::Recipe,
          url: $url ?? NULL,
        );
      }
      $this->cacheBin->set($this->getPluginId(), $projects);
    }

    // Filter by project machine name.
    if (!empty($query['machine_name'])) {
      $projects = array_filter($projects, fn(Project $project) => $project->machineName === $query['machine_name']);
    }

    // Filter by coverage.
    if (!empty($query['security_advisory_coverage'])) {
      $projects = array_filter($projects, fn(Project $project) => $project->isCovered);
    }

    // Filter by categories.
    if (!empty($query['categories'])) {
      $projects = array_filter($projects, fn(Project $project) => array_intersect(array_column($project->categories, 'id'), explode(',', $query['categories'])));
    }

    // Filter by search text.
    if (!empty($query['search'])) {
      $projects = array_filter($projects, fn(Project $project) => stripos($project->title, $query['search']) !== FALSE);
    }

    $total = count($projects);

    // Filter by sorting criterion.
    if (!empty($query['sort'])) {
      $sort = $query['sort'];
      switch ($sort) {
        case 'a_z':
          usort($projects, fn($x, $y) => $x->title <=> $y->title);
          break;

        case 'z_a':
          usort($projects, fn($x, $y) => $y->title <=> $x->title);
          break;
      }
    }

    if (array_key_exists('page', $query) && !empty($query['limit'])) {
      $projects = array_chunk($projects, $query['limit'])[$query['page']] ?? [];
    }

    return $this->createResultsPage($projects, $total);
  }

  /**
   * Prepares a Symfony Finder to search for recipes in the file system.
   *
   * @return \Symfony\Component\Finder\Finder
   *   A Symfony Finder object, configured to find locally installed recipes.
   */
  private function getFinder(): Finder {
    $search_in = [$this->appRoot . '/core/recipes'];

    // If any recipes have been installed by Composer, also search there. The
    // recipe system requires that all non-core recipes be located next to each
    // other, in the same directory.
    $contrib_recipe_names = InstalledVersions::getInstalledPackagesByType(Recipe::COMPOSER_PROJECT_TYPE);
    if ($contrib_recipe_names) {
      $path = InstalledVersions::getInstallPath($contrib_recipe_names[0]);
      $path = $this->fileSystem->realpath($path);

      $search_in[] = dirname($path);
    }

    $finder = Finder::create()
      ->files()
      ->in($search_in)
      ->depth(1)
      // Without this, recipes that are symlinked into the project (e.g.,
      // path repositories) will be missed.
      ->followLinks()
      // The example recipe exists for documentation purposes only.
      ->notPath('example/')
      ->name('recipe.yml');

    $allowed = $this->configFactory->get('project_browser.admin_settings')
      ->get('allowed_projects.' . $this->getPluginId());
    if ($allowed) {
      $finder->path(
        array_map(fn (string $name) => $name . '/', $allowed),
      );
    }
    return $finder;
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories(): array {
    return [];
  }

}
