<?php

namespace Drupal\project_browser_test\Plugin\ProjectBrowserSource;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\project_browser\Plugin\ProjectBrowserSourceBase;
use Drupal\project_browser\ProjectBrowser\Filter\BooleanFilter;
use Drupal\project_browser\ProjectBrowser\Filter\MultipleChoiceFilter;
use Drupal\project_browser\ProjectBrowser\Project;
use Drupal\project_browser\ProjectBrowser\ProjectsResultsPage;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

// cspell:ignore Edealing

/**
 * Database driven plugin.
 *
 * @ProjectBrowserSource(
 *   id = "project_browser_test_mock",
 *   label = @Translation("Project Browser Mock Plugin"),
 *   description = @Translation("Gets project and filters information from a database"),
 *   local_task = {
 *     "title" = @Translation("Browse"),
 *   }
 * )
 */
class ProjectBrowserTestMock extends ProjectBrowserSourceBase {

  /**
   * This is what the Mock understands as "Covered" modules.
   *
   * @var array
   */
  const COVERED_VALUES = ['covered'];

  /**
   * This is what the Mock understands as "Active" modules.
   *
   * @var array
   */
  const ACTIVE_VALUES = [9988, 13030];

  /**
   * This is what the Mock understands as "Maintained" modules.
   *
   * @var array
   */
  const MAINTAINED_VALUES = [13028, 19370, 9990];

  /**
   * Constructor for mock API.
   *
   * @param array $configuration
   *   The source configuration.
   * @param string $plugin_id
   *   The identifier for the plugin.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\State\StateInterface $state
   *   The session state.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBin
   *   The back end cache interface.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly LoggerInterface $logger,
    private readonly Connection $database,
    private readonly StateInterface $state,
    private readonly CacheBackendInterface $cacheBin,
    private readonly ModuleHandlerInterface $moduleHandler,
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
      $container->get('logger.factory')->get('project_browser'),
      $container->get(Connection::class),
      $container->get(StateInterface::class),
      $container->get('cache.project_browser'),
      $container->get(ModuleHandlerInterface::class),
    );
  }

  /**
   * Gets status vocabulary.
   *
   * @param int $taxonomy_id
   *   The id of the taxonomy being retrieved.
   *
   * @return array|array[]
   *   An array with the term id, name and description.
   */
  protected function getStatuses(int $taxonomy_id) {
    $body = '';
    // Development.
    if ($taxonomy_id === 46) {
      $body = '{"list":[{"tid":"13030","name":"Maintenance fixes only","description":"\u003Cp\u003EConsidered feature-complete by its maintainers.\u003C\/p\u003E","weight":"1","node_count":10,"vocabulary":{"id":"46","resource":"taxonomy_vocabulary"},"parent":[],"parents_all":[{"id":"13030","resource":"taxonomy_term"}],"feeds_item_guid":null,"feeds_item_url":null,"feed_nid":null},{"tid":"16538","name":"No further development","description":"\u003Cp\u003ENo longer developed by its maintainers.\u003C\/p\u003E","weight":"2","node_count":10,"vocabulary":{"id":"46","resource":"taxonomy_vocabulary"},"parent":[],"parents_all":[{"id":"16538","resource":"taxonomy_term"}],"feeds_item_guid":null,"feeds_item_url":null,"feed_nid":null},{"tid":"9994","name":"Obsolete","description":"\u003Cp\u003EUse of this project is deprecated.\u003C\/p\u003E","weight":"3","node_count":10,"vocabulary":{"id":"46","resource":"taxonomy_vocabulary"},"parent":[],"parents_all":[{"id":"9994","resource":"taxonomy_term"}],"feeds_item_guid":null,"feeds_item_url":null,"feed_nid":null},{"tid":"9988","name":"Under active development","description":"\u003Cp\u003EThis project is under active development.\u003C\/p\u003E","weight":"0","node_count":10,"vocabulary":{"id":"46","resource":"taxonomy_vocabulary"},"parent":[],"parents_all":[{"id":"9988","resource":"taxonomy_term"}],"feeds_item_guid":null,"feeds_item_url":null,"feed_nid":null}]}';
    }
    // Maintenance.
    elseif ($taxonomy_id === 44) {
      $body = '{"list":[{"tid":"13028","name":"Actively maintained","description":"\u003Cp\u003EMaintainers strive to actively monitor issues and respond in a timely manner.\u003C\/p\u003E","weight":"0","node_count":10,"vocabulary":{"id":"44","resource":"taxonomy_vocabulary"},"parent":[],"parents_all":[{"id":"13028","resource":"taxonomy_term"}],"feeds_item_guid":null,"feeds_item_url":null,"feed_nid":null},{"tid":"19370","name":"Minimally maintained","description":"\u003Cp\u003EMaintainers monitor issues, but fast responses are not guaranteed.\u003C\/p\u003E","weight":"1","node_count":10,"vocabulary":{"id":"44","resource":"taxonomy_vocabulary"},"parent":[],"parents_all":[{"id":"19370","resource":"taxonomy_term"}],"feeds_item_guid":null,"feeds_item_url":null,"feed_nid":null},{"tid":"9990","name":"Seeking co-maintainer(s)","description":"\u003Cp\u003EMaintainers are looking for help reviewing issues.\u003C\/p\u003E","weight":"2","node_count":10,"vocabulary":{"id":"44","resource":"taxonomy_vocabulary"},"parent":[],"parents_all":[{"id":"9990","resource":"taxonomy_term"}],"feeds_item_guid":null,"feeds_item_url":null,"feed_nid":null},{"tid":"9992","name":"Seeking new maintainer","description":"\u003Cp\u003EThe current maintainers are looking for new people to take ownership.\u003C\/p\u003E","weight":"3","node_count":10,"vocabulary":{"id":"44","resource":"taxonomy_vocabulary"},"parent":[],"parents_all":[{"id":"9992","resource":"taxonomy_term"}],"feeds_item_guid":null,"feeds_item_url":null,"feed_nid":null},{"tid":"13032","name":"Unsupported","description":"\u003Cp\u003ENot supported (i.e. abandoned), and no longer being developed. Learn more about \u003Ca href=\u0022https:\/\/www.drupal.org\/node\/251466\u0022 rel=\u0022nofollow\u0022\u003Edealing with unsupported (abandoned) projects\u003C\/a\u003E\u003C\/p\u003E","weight":"4","node_count":10,"vocabulary":{"id":"44","resource":"taxonomy_vocabulary"},"parent":[],"parents_all":[{"id":"13032","resource":"taxonomy_term"}],"feeds_item_guid":null,"feeds_item_url":null,"feed_nid":null}]}';
    }
    if (empty($body)) {
      return [];
    }
    $body = Json::decode($body);
    $list = $body['list'];
    $list = array_map(function ($item) {
      $item['id'] = $item['tid'];
      return array_intersect_key($item, array_flip(['id', 'name', 'description']));
    }, $list);

    return $list;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDevelopmentStatuses(): array {
    return $this->getStatuses(46);
  }

  /**
   * {@inheritdoc}
   */
  public function getSortOptions(): array {
    return array_diff_key(parent::getSortOptions(), ['best_match' => '']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getMaintenanceStatuses(): array {
    return $this->getStatuses(44);
  }

  /**
   * {@inheritdoc}
   */
  protected function getSecurityCoverages(): array {
    return [
      ['id' => 'covered', 'name' => 'Covered'],
      ['id' => 'not-covered', 'name' => 'Not covered'],
    ];
  }

  /**
   * Convert the sort entry within the query from received to expected by DB.
   *
   * @param array $query
   *   Query array to transform.
   */
  protected function convertSort(array &$query) {
    if (!empty($query['sort'])) {
      $options_available = $this->getSortOptions();
      if (!in_array($query['sort'], array_keys($options_available))) {
        unset($query['sort']);
      }
      else {
        // Valid value.
        switch ($query['sort']) {
          case 'usage_total':
          case 'best_match':
            $query['sort'] = 'project_usage_total';
            $query['direction'] = 'DESC';
            break;

          case 'a_z':
            $query['sort'] = 'title';
            $query['direction'] = 'ASC';
            break;

          case 'z_a':
            $query['sort'] = 'title';
            $query['direction'] = 'DESC';
            break;

          case 'created':
            $query['sort'] = 'created';
            $query['direction'] = 'DESC';
            break;

        }
      }
    }
  }

  /**
   * Converts the maintenance entry from received to expected by DB.
   *
   * @param array $query
   *   Query array to transform.
   */
  protected function convertMaintenance(array &$query) {
    if (!empty($query['maintenance_status'])) {
      $query['maintenance_status'] = self::MAINTAINED_VALUES;
    }
    else {
      unset($query['maintenance_status']);
    }
  }

  /**
   * Converts the development entry from received to expected by DB.
   *
   * @param array $query
   *   Query array to transform.
   */
  protected function convertDevelopment(array &$query) {
    if (!empty($query['development_status'])) {
      $query['development_status'] = self::ACTIVE_VALUES;
    }
    else {
      unset($query['development_status']);
    }
  }

  /**
   * Converts the security entry from received to expected by DB.
   *
   * @param array $query
   *   Query array to transform.
   */
  protected function convertSecurity(array &$query) {
    if (!empty($query['security_advisory_coverage'])) {
      $query['security_advisory_coverage'] = self::COVERED_VALUES;
    }
    else {
      $query['security_advisory_coverage'] = array_column(
        $this->getSecurityCoverages(),
        'id'
      );
    }
  }

  /**
   * Convert the search values from available ones to expected ones.
   *
   * The values that were given as available for the search need to be the
   * actual values that will be queried within the search function.
   *
   * @param array $query
   *   Query parameters to check.
   *
   * @return array
   *   Query parameters converted to the values expected by the search function.
   */
  protected function convertQueryOptions(array $query = []): array {
    $this->convertSort($query);
    $this->convertMaintenance($query);
    $this->convertDevelopment($query);
    $this->convertSecurity($query);

    return $query;
  }

  /**
   * Returns category data keyed by category ID.
   *
   * @return array
   *   The category ID and name, keyed by ID.
   */
  protected function getCategoryData(): array {
    $module_path = $this->moduleHandler->getModule('project_browser')->getPath();
    $category_list = Json::decode(file_get_contents($module_path . '/tests/fixtures/category_list.json')) ?? [];
    $categories = [];
    foreach ($category_list as $category) {
      $categories[$category['tid']] = [
        'id' => $category['tid'],
        'name' => $category['name'],
      ];
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

    $filters_to_define = $this->state->get('filters_to_define');
    if ($filters_to_define !== NULL) {
      // Only keep those filters which needs to be defined according to
      // $filters_to_define.
      foreach ($filters as $filter_key => $filter_value) {
        if (!in_array($filter_key, $filters_to_define, TRUE)) {
          unset($filters[$filter_key]);
        }
      }
    }
    return $filters;
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories(): array {
    // Rekey the array to avoid JSON considering it an object.
    return array_values($this->getCategoryData());
  }

  /**
   * {@inheritdoc}
   */
  public function getProjects(array $query = []) : ProjectsResultsPage {
    $api_response = $this->fetchProjects($query);
    $categories = $this->getCategoryData();

    $returned_list = [];
    if ($api_response) {
      foreach ($api_response['list'] as $project_data) {
        $avatar_url = 'https://git.drupalcode.org/project/' . $project_data['field_project_machine_name'] . '/-/avatar';
        $logo = [
          'file' => [
            'uri' => $avatar_url,
            'resource' => 'image',
          ],
          'alt' => 'Project logo',
        ];

        $returned_list[] = new Project(
          logo: $logo,
          // Mock projects are filtered and made sure that they are compatible
          // before we even put them in the database.
          isCompatible: TRUE,
          isMaintained: in_array($project_data['maintenance_status'], self::MAINTAINED_VALUES),
          isCovered: in_array($project_data['field_security_advisory_coverage'], self::COVERED_VALUES),
          projectUsageTotal: array_reduce($project_data['project_data']['project_usage'] ?? [], fn($total, $project_usage) => $total + $project_usage) ?: 0,
          machineName: $project_data['field_project_machine_name'],
          body: $this->relativeToAbsoluteUrls($project_data['project_data']['body'], 'https://www.drupal.org'),
          title: $project_data['title'],
          author: ['name' => $project_data['author']],
          packageName: 'drupal/' . $project_data['field_project_machine_name'],
          url: Url::fromUri('https://www.drupal.org/project/' . $project_data['field_project_machine_name']),
          // Add name property to each category, so it can be rendered.
          categories: array_map(fn($category) => $categories[$category['id']] ?? '', $project_data['project_data']['taxonomy_vocabulary_3'] ?? []),
          images: $project_data['project_data']['field_project_images'] ?? [],
          warnings: $this->getWarnings($project_data),
          id: $project_data['field_project_machine_name'],
        );
      }
    }

    return $this->createResultsPage($returned_list, $api_response['total_results'] ?? 0);
  }

  /**
   * Fetches the projects from the mock backend.
   *
   * Here, we're querying the local database, populated from the fixture.
   */
  protected function fetchProjects($query) {
    $query = $this->convertQueryOptions($query);
    try {
      $db_query = $this->database->select('project_browser_projects', 'pbp')
        ->fields('pbp')
        ->condition('pbp.status', 1);

      if (array_key_exists('machine_name', $query)) {
        $db_query->condition('field_project_machine_name', $query['machine_name']);
      }

      if (array_key_exists('sort', $query) && !empty($query['sort'])) {
        $sort = $query['sort'];
        $direction = (array_key_exists('direction', $query) && $query['direction'] == 'ASC') ? 'ASC' : 'DESC';
        $db_query->orderBy($sort, $direction);
      }
      else {
        // Default order.
        $db_query->orderBy('project_usage_total', 'DESC');
      }

      // Filter by maintenance status.
      if (array_key_exists('maintenance_status', $query)) {
        $db_query->condition('maintenance_status', $query['maintenance_status'], 'IN');
      }

      // Filter by development status.
      if (array_key_exists('development_status', $query)) {
        $db_query->condition('development_status', $query['development_status'], 'IN');
      }

      // Filter by security advisory coverage.
      if (array_key_exists('security_advisory_coverage', $query)) {
        $db_query->condition('field_security_advisory_coverage', $query['security_advisory_coverage'], 'IN');
      }

      // Filter by category.
      if (!empty($query['categories'])) {
        $tids = explode(',', $query['categories']);
        $db_query->join('project_browser_categories', 'cat', 'pbp.nid = cat.pid');
        $db_query->condition('cat.tid', $tids, 'IN');
      }

      // Filter by search term.
      if (array_key_exists('search', $query)) {
        $search = $query['search'];
        $db_query->condition('pbp.project_data', "%$search%", 'LIKE');
      }
      $db_query->groupBy('pbp.nid');

      // If there is a specified limit, then this is a list of multiple
      // projects.
      $total_results = $db_query->countQuery()
        ->execute()
        ->fetchField();
      $offset = $query['page'] ?? 0;
      $limit = $query['limit'] ?? 50;
      $db_query->range($limit * $offset, $limit);
      $result = $db_query
        ->execute()
        ->fetchAll();
      $db_projects = array_map(function ($project_data) {
        $data = (array) $project_data;
        $data['project_data'] = unserialize($project_data->project_data);
        return $data;
      }, $result);

      if (count($db_projects) > 0) {
        $drupal_org_response['list'] = $db_projects;
        $drupal_org_response['total_results'] = $total_results;
        return $drupal_org_response;
      }

      return FALSE;
    }
    catch (\Exception $exception) {
      $this->logger->error($exception->getMessage());
      return FALSE;
    }
  }

  /**
   * Determines warning messages based on development and maintenance status.
   *
   * @param array $project
   *   A project array.
   *
   * @return string[]
   *   An array of warning messages.
   */
  protected function getWarnings(array $project) {
    // This is based on logic from Drupal.org.
    // @see https://git.drupalcode.org/project/drupalorg/-/blob/e31465608d1380345834/drupalorg_project/drupalorg_project.module
    $warnings = [];
    $merged_vocabularies = array_merge($this->getDevelopmentStatuses(), $this->getMaintenanceStatuses());
    $statuses = array_column($merged_vocabularies, 'description', 'id');
    foreach (['taxonomy_vocabulary_44', 'taxonomy_vocabulary_46'] as $field) {
      // Maintenance status is not Actively maintained and Development status is
      // not Under active development.
      $id = $project[$field]['id'] ?? FALSE;
      if ($id && !in_array($id, [13028, 9988])) {
        // Maintenance status is Abandoned, or Development status is No further
        // development or Obsolete.
        if (in_array($id, [13032, 16538, 9994])) {
          $warnings[] = $statuses[$id];
        }
      }
    }
    return $warnings;
  }

  /**
   * Convert relative URLs found in the body to absolute URLs.
   *
   * @param array $body
   *   Body array field containing summary and value properties.
   * @param string $base_url
   *   Base URL to prepend to relative links.
   *
   * @return array
   *   Body array with relative URLs converted to absolute ones.
   */
  protected function relativeToAbsoluteUrls(array $body, string $base_url) {
    if (empty($body['value'])) {
      $body['value'] = $body['summary'] ?? '';
    }
    $body['value'] = Html::transformRootRelativeUrlsToAbsolute($body['value'], $base_url);
    return $body;
  }

  /**
   * Checks if a project's security coverage has been revoked.
   *
   * @param \Drupal\project_browser\ProjectBrowser\Project $project
   *   The project to check.
   *
   * @return bool
   *   False if the project's security coverage is revoked, otherwise true.
   */
  public function isProjectSafe(Project $project): bool {
    if ($project->machineName === 'security_revoked_module') {
      return FALSE;
    }

    return TRUE;
  }

}
