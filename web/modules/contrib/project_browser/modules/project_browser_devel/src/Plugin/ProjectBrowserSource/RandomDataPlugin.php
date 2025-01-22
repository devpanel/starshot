<?php

namespace Drupal\project_browser_devel\Plugin\ProjectBrowserSource;

use Drupal\Component\Utility\Random;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\project_browser\Plugin\ProjectBrowserSourceBase;
use Drupal\project_browser\ProjectBrowser\Filter\BooleanFilter;
use Drupal\project_browser\ProjectBrowser\Filter\MultipleChoiceFilter;
use Drupal\project_browser\ProjectBrowser\Project;
use Drupal\project_browser\ProjectBrowser\ProjectsResultsPage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Random data plugin. Used mostly for testing.
 *
 * To enable this source use the following drush command.
 * phpcs:ignore
 *   drush config:set project_browser.admin_settings enabled_source random_data
 *
 * @ProjectBrowserSource(
 *   id = "random_data",
 *   label = @Translation("Random data"),
 *   description = @Translation("Gets random project and filters information"),
 *   local_task = {}
 * )
 */
class RandomDataPlugin extends ProjectBrowserSourceBase {

  /**
   * Utility to create random data.
   *
   * @var \Drupal\Component\Utility\Random
   */
  protected $randomGenerator;

  /**
   * ProjectBrowser cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBin;

  /**
   * Constructs a MockDrupalDotOrg object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_bin
   *   The cache bin.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CacheBackendInterface $cache_bin) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->randomGenerator = new Random();
    $this->cacheBin = $cache_bin;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('cache.project_browser'),
    );
  }

  /**
   * Generate random IDs and labels.
   *
   * @param int $array_length
   *   Length of the array to generate.
   *
   * @return array
   *   Array of random IDs and names.
   */
  protected function getRandomIdsAndNames($array_length = 4): array {
    $data = [];
    for ($i = 0; $i < $array_length; $i++) {
      $data[] = [
        'id' => uniqid(),
        'name' => ucwords($this->randomGenerator->word(rand(6, 10))),
      ];
    }

    return $data;
  }

  /**
   * Returns a random date.
   *
   * @return int
   *   Random timestamp.
   */
  protected function getRandomDate() {
    return rand(strtotime('2 years ago'), strtotime('today'));
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories(): array {
    $stored_categories = $this->cacheBin->get('RandomData:categories');
    if ($stored_categories) {
      $categories = $stored_categories->data;
    }
    else {
      $categories = $this->getRandomIdsAndNames(20);
      $this->cacheBin->set('RandomData:categories', $categories);
    }
    return $categories;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilterDefinitions(): array {
    $filters = [];

    $categories = $this->getCategories();
    $choices = array_combine(
      array_column($categories, 'id'),
      array_column($categories, 'name'),
    );
    $filters['categories'] = new MultipleChoiceFilter($choices, [], $this->t('Categories'), NULL);

    $filters['securityCoverage'] = new BooleanFilter(
      TRUE,
      $this->t('Show projects covered by a security policy'),
      $this->t('Show all'),
      $this->t('Security advisory coverage'),
      NULL,
    );
    $filters['maintenanceStatus'] = new BooleanFilter(
      TRUE,
      $this->t('Show actively maintained projects'),
      $this->t('Show all'),
      $this->t('Maintenance status'),
      NULL,
    );
    $filters['developmentStatus'] = new BooleanFilter(
      FALSE,
      $this->t('Show projects under active development'),
      $this->t('Show all'),
      $this->t('Development status'),
      NULL,
    );

    return $filters;
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

    // Filter by categories.
    if (!empty($query['categories'])) {
      $projects = array_filter($projects, fn(Project $project) => array_intersect(array_column($project->categories, 'id'), explode(',', $query['categories'])));
    }

    // Filter by search text.
    if (!empty($query['search'])) {
      $projects = array_filter($projects, fn(Project $project) => stripos($project->title, $query['search']) !== FALSE);
    }

    return $this->createResultsPage($projects);
  }

  /**
   * Gets the project data from cache if available, or builds it if not.
   */
  private function getProjectData(): array {
    $stored_projects = $this->cacheBin->get('RandomData:projects');
    if ($stored_projects) {
      return $stored_projects->data;
    }

    $projects = [];
    $number_of_projects = rand(16, 36);
    $categories = $this->getCategories();
    $broken_image = 'https://image.not/found' . uniqid() . '.jpg';
    $good_image = 'https://picsum.photos/600/400';
    for ($i = 0; $i < $number_of_projects; $i++) {
      $machine_name = strtolower($this->randomGenerator->word(10));
      $project_images = [];
      if ($i !== 0) {
        $project_images[] = [
          'file' => [
            'uri' => str_replace(4, 5, $good_image),
            'resource' => 'image',
          ],
          'alt' => $machine_name . ' something',
        ];
        $project_images[] = [
          'file' => [
            'uri' => str_replace(4, 6, $good_image),
            'resource' => 'image',
          ],
          'alt' => $machine_name . ' another thing',
        ];
      }

      $projects[] = new Project(
        logo: [
          'file' => [
            'uri' => ($i % 3) ? $good_image : $broken_image,
            'resource' => 'image',
          ],
          'alt' => $machine_name . ' logo',
        ],
        isCompatible: (bool) ($i / 4),
        isMaintained: (bool) rand(0, 1),
        isCovered: (bool) rand(0, 1),
        projectUsageTotal: rand(0, 100000),
        machineName: $machine_name,
        body: [
          'summary' => $this->randomGenerator->paragraphs(1),
          'value' => $this->randomGenerator->paragraphs(5),
        ],
        title: ucwords($machine_name),
        author: [
          'name' => $this->randomGenerator->word(10),
        ],
        packageName: 'random/' . $machine_name,
        categories: [$categories[array_rand($categories)]],
        images: $project_images,
        id: $machine_name,
      );
    }
    $this->cacheBin->set('RandomData:projects', $projects);
    return $projects;
  }

}
