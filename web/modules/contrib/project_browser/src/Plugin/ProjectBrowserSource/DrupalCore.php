<?php

namespace Drupal\project_browser\Plugin\ProjectBrowserSource;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Site\Settings;
use Drupal\project_browser\Plugin\ProjectBrowserSourceBase;
use Drupal\project_browser\ProjectBrowser\Project;
use Drupal\project_browser\ProjectBrowser\ProjectsResultsPage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The source plugin to get Drupal core projects list.
 *
 * @ProjectBrowserSource(
 *   id = "drupal_core",
 *   label = @Translation("Core modules"),
 *   description = @Translation("Modules included in Drupal core"),
 * )
 */
class DrupalCore extends ProjectBrowserSourceBase {

  /**
   * All core modules are covered under security policy.
   *
   * @var string
   */
  const COVERED = 'covered';

  /**
   * All core modules are "Active" modules.
   *
   * @var string
   */
  const ACTIVE = 'active';

  /**
   * All core modules are "Maintained" modules.
   *
   * @var string
   */
  const MAINTAINED = 'maintained';

  /**
   * Constructor for Drupal Core plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request from the browser.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBin
   *   The cache back end interface.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The list of available modules.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly RequestStack $requestStack,
    private readonly CacheBackendInterface $cacheBin,
    private readonly ModuleExtensionList $moduleExtensionList,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get(RequestStack::class),
      $container->get('cache.project_browser'),
      $container->get(ModuleExtensionList::class),
    );
  }

  /**
   * Filters module extension list for core modules.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   The array containing core modules, keyed by module machine name.
   */
  protected function getCoreModules() {
    $projects = array_filter($this->moduleExtensionList->reset()->getList(), fn(Extension $project) => $project->origin === 'core');
    $include_tests = Settings::get('extension_discovery_scan_tests') || drupal_valid_test_ua();
    if (!$include_tests) {
      $projects = array_filter($projects, fn(Extension $project) => empty($project->info['hidden']) && $project->info['package'] !== 'Testing');
    }
    return $projects;
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories(): array {
    $categories = [];
    foreach ($this->getCoreModules() as $module) {
      $categories[$module->info['package']] = [
        'name' => $module->info['package'],
        'id' => $module->info['package'],
      ];
    }
    usort($categories, fn($a, $b) => $a['id'] <=> $b['id']);
    return $categories;
  }

  /**
   * {@inheritdoc}
   */
  public function getProjects(array $query = []) : ProjectsResultsPage {
    $projects = $this->getProjectData();

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
    $project_count = count($projects);
    if (!empty($query['page']) && !empty($query['limit'])) {
      $projects = array_chunk($projects, $query['limit'])[$query['page']] ?? [];
    }
    return $this->createResultsPage($projects, $project_count);
  }

  /**
   * Gets the project data from cache if available, or builds it if not.
   *
   * @return \Drupal\project_browser\ProjectBrowser\Project[]
   *   Array of projects.
   */
  protected function getProjectData(): array {
    $stored_projects = $this->cacheBin->get('DrupalCore:projects');
    if ($stored_projects) {
      return $stored_projects->data;
    }

    $request = $this->requestStack->getCurrentRequest();
    $returned_list = [];
    foreach ($this->getCoreModules() as $module_name => $module) {
      // Dummy data is used for the fields that are unavailable for core
      // modules.
      $returned_list[] = new Project(
        logo: [
          'file' => [
            'uri' => $request->getSchemeAndHttpHost() . '/core/misc/logo/drupal-logo.svg',
            'resource' => 'image',
          ],
          'alt' => '',
        ],
        // All core projects are considered compatible.
        isCompatible: TRUE,
        isMaintained: TRUE,
        isCovered: $module->info['package'] !== 'Core (Experimental)',
        machineName: $module_name,
        body: [
          'summary' => $module->info['description'],
          'value' => $module->info['description'],
        ],
        title: $module->info['name'],
        author: [
          'name' => 'Drupal Core',
        ],
        packageName: 'drupal/core',
        categories: [
          [
            'id' => $module->info['package'],
            'name' => $module->info['package'],
          ],
        ],
        id: $module_name,
      );
    }

    $this->cacheBin->set('DrupalCore:projects', $returned_list);
    return $returned_list;
  }

  /**
   * {@inheritdoc}
   */
  public function getSortOptions(): array {
    return [
      'a_z' => [
        'id' => 'a_z',
        'text' => $this->t('A-Z'),
      ],
      'z_a' => [
        'id' => 'z_a',
        'text' => $this->t('Z-A'),
      ],
    ];
  }

}
