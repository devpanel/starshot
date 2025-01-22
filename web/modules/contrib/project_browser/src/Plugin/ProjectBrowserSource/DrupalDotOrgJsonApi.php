<?php

namespace Drupal\project_browser\Plugin\ProjectBrowserSource;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ExtensionVersion;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\project_browser\Plugin\ProjectBrowserSourceBase;
use Drupal\project_browser\ProjectBrowser\Filter\BooleanFilter;
use Drupal\project_browser\ProjectBrowser\Filter\MultipleChoiceFilter;
use Drupal\project_browser\ProjectBrowser\Project;
use Drupal\project_browser\ProjectBrowser\ProjectsResultsPage;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Drupal.org JSON:API endpoint.
 *
 * @ProjectBrowserSource(
 *   id = "drupalorg_jsonapi",
 *   label = @Translation("Contrib modules"),
 *   description = @Translation("Modules on Drupal.org queried via the JSON:API endpoint"),
 *   local_task = {
 *     "title" = @Translation("Browse"),
 *   }
 * )
 */
class DrupalDotOrgJsonApi extends ProjectBrowserSourceBase {

  use StringTranslationTrait;

  /**
   * Main domain endpoint.
   *
   * @const string
   */
  const DRUPAL_ORG_ENDPOINT = 'https://www.drupal.org';

  /**
   * Endpoint to query data from.
   *
   * @const string
   */
  const JSONAPI_ENDPOINT = self::DRUPAL_ORG_ENDPOINT . '/jsonapi';

  /**
   * Value of the revoked status in the security coverage field.
   *
   * @const string
   */
  const REVOKED_STATUS = 'revoked';

  /**
   * This is what drupal.org plugin understands as "Covered" modules.
   *
   * @var array
   */
  const COVERED_VALUES = ['covered'];

  /**
   * Constructs a MockDrupalDotOrg object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   A Guzzle client object.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBin
   *   The cache bin.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected LoggerInterface $logger,
    protected ClientInterface $httpClient,
    protected CacheBackendInterface $cacheBin,
    protected TimeInterface $time,
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
      $container->get(ClientInterface::class),
      $container->get('cache.project_browser'),
      $container->get('datetime.time'),
    );
  }

  /**
   * Performs a request to the jsonapi and returns the results.
   *
   * @param string $url
   *   URL to query.
   * @param array $query_params
   *   Params to pass to the query.
   * @param bool $all_data
   *   Fetch all data from all pages (defaults to FALSE).
   *
   * @return array
   *   Results from the query.
   */
  protected function fetchData(string $url, array $query_params = [], bool $all_data = FALSE): array {
    // Failsafe to avoid timeouts or memory issues.
    // 50 results per page x 10 iterations = 500 results.
    $iteration_limit = 10;
    $result = [
      'code' => NULL,
      'data' => NULL,
      'message' => '',
    ];
    $params = [];
    try {
      if (!empty($query_params)) {
        $params = [
          'query' => $query_params,
        ];
      }
      $response = $this->httpClient->request('GET', $url, $params);
      $response_data = Json::decode($response->getBody()->getContents());

      $result['code'] = $response->getStatusCode();
      $result['data'] = $response_data['data'];
      $result['meta'] = $response_data['meta'] ?? NULL;
      if (!empty($response_data['included'])) {
        $result['included'] = $response_data['included'];
      }
      if ($all_data) {
        // Start querying the "next" pages until there are no more of them, or
        // we reach the iteration max limit.
        $iterations = 0;
        while (!empty($response_data['links']['next']) && $iterations < $iteration_limit) {
          // Params are already in the URL for the "next" request.
          $url = $response_data['links']['next']['href'];
          $response = $this->httpClient->request('GET', $url);
          $response_data = Json::decode($response->getBody()->getContents());

          $result['data'] = array_merge($result['data'], $response_data['data']);
          if (!empty($response_data['included'])) {
            $result['included'] = array_merge($result['included'], $response_data['included']);
          }
          $iterations++;
        }

        if ($iterations >= $iteration_limit) {
          $result['message'] = $this->t('Max limit reached: Result data has been truncated to %limit records.', [
            '%limit' => count($result['data']),
          ]);
        }
      }
    }
    catch (RequestException $exception) {
      $this->logger->error($exception->getMessage());
      $result['message'] = $exception->getMessage();
      $result['code'] = $exception->getCode();
    }
    catch (GuzzleException $exception) {
      $this->logger->error($exception->getMessage());
      $result['message'] = $exception->getMessage();
      $result['code'] = $exception->getCode();
    }
    catch (\Throwable $exception) {
      $this->logger->error($exception->getMessage());
      $result['message'] = $exception->getMessage();
      $result['code'] = Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    return $result;
  }

  /**
   * Processes the included data returned by jsonapi and map by type.
   *
   * @param array $included
   *   Data from jsonapi with all included information.
   *
   * @return array
   *   Mapped array keyed by type and id.
   */
  protected function mapIncludedData(array $included): array {
    $mapped_array = [];
    foreach ($included as $item) {
      $mapped_array[$item['type']][$item['id']] = $item['attributes'];
    }

    return $mapped_array;
  }

  /**
   * Process the return of a query to a vocabulary endpoint.
   *
   * @param string $vocabulary
   *   Vocabulary to query.
   *
   * @return array[]
   *   Result in array format.
   */
  protected function getVocabularyData(string $vocabulary): array {
    $endpoint = self::JSONAPI_ENDPOINT . '/taxonomy_term/' . $vocabulary;
    $query_params = [
      'sort' => 'name',
      'filter[status]' => 1,
      'fields[taxonomy_term--' . $vocabulary . ']' => 'name',
    ];
    $result = $this->fetchData($endpoint, $query_params, TRUE);

    $return = [];
    if ($result['code'] == Response::HTTP_OK && !empty($result['data'])) {
      foreach ($result['data'] as $item) {
        $return[] = [
          'id' => $item['id'],
          'name' => $item['attributes']['name'],
        ];
      }
    }

    return $return;
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
  public function getCategories(): array {
    return $this->getVocabularyData('module_categories');
  }

  /**
   * {@inheritdoc}
   */
  public function getProjects(array $query = []): ProjectsResultsPage {
    $filter_values = $this->filterValues();
    if (!empty($filter_values['drupal_version']) && $filter_values['drupal_version']['supported'] === FALSE) {
      $error_message = $filter_values['drupal_version']['message'] ?? $this->t('The current version of Drupal is not supported in the Drupal.org endpoint.');
      return $this->createResultsPage([], 0, $error_message);
    }

    $api_response = $this->fetchProjects($query);
    if (!is_array($api_response) || $api_response['code'] !== Response::HTTP_OK) {
      $error_message = $api_response['message'] ?? $this->t('Error querying data.');
      return $this->createResultsPage([], 0, $error_message);
    }

    $returned_list = [];
    if (is_array($api_response) && !empty($api_response['list'])) {
      $related = !empty($api_response['related']) ? $api_response['related'] : NULL;
      $current_drupal_version = $this->getNumericSemverVersion(\Drupal::VERSION);
      $maintained_values = $filter_values['maintained'] ?? [];
      foreach ($api_response['list'] as $project) {
        // Map any properties from jsonapi format to the simplified record
        // format used by Project Browser.
        $machine_name = $project['attributes']['field_project_machine_name'];
        $uid_info = $project['relationships']['uid']['data'];

        $maintenance_status = $project['relationships']['field_maintenance_status']['data'] ?? [];
        if (!empty($maintenance_status)) {
          $maintenance_status = [
            'id' => $maintenance_status['id'],
            'name' => $related[$maintenance_status['type']][$maintenance_status['id']]['name'],
          ];
        }

        $development_status = $project['relationships']['field_development_status']['data'] ?? [];
        if (!empty($development_status)) {
          $development_status = [
            'id' => $development_status['id'],
            'name' => $related[$development_status['type']][$development_status['id']]['name'],
          ];
        }

        $module_categories = $project['relationships']['field_module_categories']['data'] ?? [];
        if (!empty($module_categories)) {
          $categories = [];
          foreach ($module_categories as $module_category) {
            $categories[] = [
              'id' => $module_category['id'],
              'name' => $related[$module_category['type']][$module_category['id']]['name'],
            ];
          }
          $module_categories = $categories;
        }

        $project_images = $project['relationships']['field_project_images']['data'] ?? [];
        if (!empty($project_images)) {
          $images = [];
          foreach ($project_images as $image) {
            $uri = self::DRUPAL_ORG_ENDPOINT . $related[$image['type']][$image['id']]['uri']['url'];
            // Adapt the path as we are querying via www.drupal.org.
            $uri = str_replace(self::DRUPAL_ORG_ENDPOINT . '/assets/', self::DRUPAL_ORG_ENDPOINT . '/files/', $uri);
            $images[] = [
              'file' => [
                'uri' => $uri,
                'resource' => 'image',
              ],
              'alt' => $image['meta']['alt'] ?? '',
            ];
          }
          $project_images = $images;
        }

        $project_usage = $project['attributes']['field_active_installs'];
        $project_usage_total = 0;
        if ($project_usage) {
          $project_usage = Json::decode($project_usage);
          foreach ($project_usage as $value) {
            $project_usage_total += (int) $value;
          }
        }

        $is_compatible = FALSE;
        $semver_minimum = (int) $project['attributes']['field_core_semver_minimum'];
        $semver_maximum = (int) $project['attributes']['field_core_semver_maximum'];
        if (($semver_minimum <= $current_drupal_version) && ($semver_maximum >= $current_drupal_version)) {
          $is_compatible = TRUE;
        }

        $logo = [];
        if (!empty($project['attributes']['field_logo_url'])) {
          $logo = [
            'file' => [
              'uri' => $project['attributes']['field_logo_url']['uri'],
              'resource' => 'image',
            ],
            'alt' => $project['attributes']['title'] . ' logo',
          ];
        }

        $body = $this->bodyRelativeToAbsoluteUrls(
          $project['attributes']['body'] ?? ['summary' => '', 'value' => ''], 'https://www.drupal.org');
        $project_object = new Project(
          logo: $logo,
          isCompatible: $is_compatible,
          isMaintained: in_array($maintenance_status['id'], $maintained_values),
          isCovered: in_array($project['attributes']['field_security_advisory_coverage'], self::COVERED_VALUES),
          projectUsageTotal: $project_usage_total,
          machineName: $machine_name,
          body: $body,
          title: $project['attributes']['title'],
          author: [
            'name' => $related[$uid_info['type']][$uid_info['id']]['name'],
          ],
          packageName: $project['attributes']['field_composer_namespace'] ?? 'drupal/' . $machine_name,
          categories: $module_categories,
          images: $project_images,
          url: Url::fromUri('https://www.drupal.org/project/' . $machine_name),
        );
        $returned_list[] = $project_object;
      }
    }

    return $this->createResultsPage($returned_list, $api_response['total_results'] ?? 0);
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
  protected function bodyRelativeToAbsoluteUrls(array $body, string $base_url = self::DRUPAL_ORG_ENDPOINT): array {
    if (empty($body['value'])) {
      $body['value'] = $body['summary'] ?? '';
    }
    $body['value'] = Html::transformRootRelativeUrlsToAbsolute($body['value'], $base_url);

    return $body;
  }

  /**
   * Maps a given field to the allowed fields to sort results.
   *
   * @param string $field
   *   Name of the field.
   *
   * @return null|string
   *   Mapped field name or NULL if not available.
   *
   * @see ProjectBrowserSourceBase::getSortOptions()
   */
  protected function mapSortField(string $field): ?string {
    $map = [
      'usage_total' => 'active_installs_total',
      'created' => 'created',
      // 'best_match' would be the default sort.
      'best_match' => NULL,
      'a_z' => 'title',
      'z_a' => 'title',
    ];

    return $map[$field] ?? NULL;
  }

  /**
   * Maps a given field to the direction within the allowed values.
   *
   * @param string $field
   *   Name of the field.
   *
   * @return null|string
   *   Mapped direction or NULL if not available.
   *
   * @see ProjectBrowserSourceBase::getSortOptions()
   */
  protected function mapSortDirection(string $field): ?string {
    $map = [
      'usage_total' => 'DESC',
      'created' => 'DESC',
      // 'best_match' would be the default sort.
      'best_match' => NULL,
      'a_z' => 'ASC',
      'z_a' => 'DESC',
    ];

    return $map[$field] ?? NULL;
  }

  /**
   * Fetches the projects from the jsonapi backend.
   *
   * @param array $query
   *   Query parameters.
   *
   * @return array
   *   Array containing the results and the total number of records.
   */
  protected function fetchProjects(array $query): array {
    $endpoint = self::JSONAPI_ENDPOINT . '/index/project_modules';
    $query = $this->convertQueryOptions($query);

    $query_params = [
      'filter[status]' => 1,
      // For now, we only want full "module" projects.
      'filter[type]' => 'project_module',
      'filter[project_type]' => 'full',
      'page[limit]' => $query['limit'],
      'page[offset]' => $query['limit'] * $query['page'],
      'include' => 'field_supporting_organizations,field_supporting_organizations.field_supporting_organization,field_module_categories,field_maintenance_status,field_development_status,uid,field_project_images',
    ];

    if (!is_null($query['sort'])) {
      $query_params['sort'] = $query['sort'];
    }

    if (!empty($query['search'])) {
      $query_params['filter[fulltext]'] = $query['search'];
    }

    if (!empty($query['machine_name'])) {
      $query_params['filter[machine_name]'] = $query['machine_name'];
    }

    // For now, we only want compatible projects.
    $query_params = $this->addCoreVersionCheck($query_params);

    $query_params = $this->addQueryParamsMultivalue('module_categories_uuid', $query['categories'] ?? '', $query_params);
    $query_params = $this->addQueryParamsMultivalue('maintenance_status_uuid', $query['maintenance_status'] ?? '', $query_params);
    $query_params = $this->addQueryParamsMultivalue('development_status_uuid', $query['development_status'] ?? '', $query_params);
    $query_params = $this->addQueryParamsMultivalue('security_coverage', $query['security_advisory_coverage'] ?? '', $query_params);
    // We will never want 'revoked' projects.
    $query_params = $this->addQueryParamsMultivalue('security_coverage', self::REVOKED_STATUS, $query_params, TRUE);

    $result = $this->fetchData($endpoint, $query_params);
    $return = [
      'code' => $result['code'],
      'total_results' => 0,
      'list' => [],
    ];
    if ($result['code'] === Response::HTTP_OK && !empty($result['data'])) {
      // Related data referenced by any possible data entry.
      $included = !empty($result['included']) ? $this->mapIncludedData($result['included']) : FALSE;

      $return['related'] = $included;
      $return['total_results'] = $result['meta']['count'] ?? count($result['data']);
      $return['list'] = $result['data'];
    }

    if ($result['code'] !== Response::HTTP_OK) {
      $return['message'] = $result['message'] ?? $this->t('Error when fetching the data.');
    }

    return $return;
  }

  /**
   * Translates a numeric semver version into a number in the expected format.
   *
   * It will do three blocks of three digits with padding zeros to the left.
   * ie:
   * - 9.3.6 will translate to 9003006.
   * - 10.4.12 will translate to 10004012.
   *
   * @param string $version
   *   Semver version to check. It should follow X.Y.Z format.
   *
   * @return int
   *   Numeric representation of the given version.
   */
  protected function getNumericSemverVersion(string $version): int {
    $version_object = ExtensionVersion::createFromVersionString($version);
    if ($extra = $version_object->getVersionExtra()) {
      $version = str_replace("-$extra", '', $version);
    }
    $minor_version = $version_object->getMinorVersion() ?? 0;
    $patch_version = explode('.', $version)[2] ?? '0';

    return (int) (
      $version_object->getMajorVersion() .
      str_pad($minor_version, 3, '0', STR_PAD_LEFT) .
      str_pad($patch_version, 3, '0', STR_PAD_LEFT)
    );
  }

  /**
   * Build the right query based on the field name and the values given.
   *
   * @param string $field_name
   *   Vocabulary to query.
   * @param string $values
   *   Comma-separated list of values to check, if any.
   * @param array $query_params
   *   Query params that will be passed to the request.
   * @param bool $negate
   *   Make the query a 'NOT IN' instead of an 'IN'.
   *
   * @return array
   *   New list of params containing the new filters.
   */
  protected function addQueryParamsMultivalue($field_name, string $values, array $query_params, $negate = FALSE): array {
    if (!empty($values)) {
      $values = explode(',', $values);
      $operator = ($negate) ? 'NOT IN' : 'IN';
      $field = ($negate) ? 'n_' . $field_name : $field_name;
      $index = 0;
      foreach ($values as $value) {
        $value = trim($value);
        $query_params['filter[' . $field . '][value][' . $index . ']'] = $value;
        $index++;
      }
      $query_params['filter[' . $field . '][operator]'] = $operator;
      $query_params['filter[' . $field . '][path]'] = $field_name;
    }

    return $query_params;
  }

  /**
   * Add the core version filters to the query.
   *
   * @param array $query_params
   *   Query params that will be passed to the request.
   *
   * @return array
   *   New list of params containing the new filters.
   */
  protected function addCoreVersionCheck(array $query_params): array {
    $current_drupal_version = $this->getNumericSemverVersion(\Drupal::VERSION);
    if ($current_drupal_version) {
      $field = 'core_semver_minimum';
      $query_params['filter[' . $field . '][value]'] = $current_drupal_version;
      $query_params['filter[' . $field . '][operator]'] = '<=';
      $query_params['filter[' . $field . '][path]'] = $field;

      $field = 'core_semver_maximum';
      $query_params['filter[' . $field . '][value]'] = $current_drupal_version;
      $query_params['filter[' . $field . '][operator]'] = '>=';
      $query_params['filter[' . $field . '][path]'] = $field;
    }

    return $query_params;
  }

  /**
   * Returns the filter values from the www.drupal.org endpoint.
   *
   * @return array
   *   Filter values by taxonomy.
   */
  protected function filterValues(): array {
    $values = [];
    $url = self::DRUPAL_ORG_ENDPOINT . '/drupalorg-api/project-browser-filters';
    $url .= '?drupal_version=' . \Drupal::VERSION;
    $filter_values = $this->cacheBin->get('DrupalDotOrgJsonApi:filter_values');
    if ($filter_values) {
      $values = $filter_values->data;
    }
    else {
      $expiry_time = $this->time->getRequestTime() + 3600;
      try {
        $response = $this->httpClient->request('GET', $url);
        $values = Json::decode($response->getBody()->getContents());
        $this->cacheBin->set('DrupalDotOrgJsonApi:filter_values', $values, $expiry_time);
      }
      catch (GuzzleException $exception) {
        $this->logger->error($exception->getMessage());
      }
      catch (\Throwable $exception) {
        $this->logger->error($exception->getMessage());
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  protected function convertQueryOptions(array $query = []): array {
    $filter_values = $this->filterValues();
    $active_values = $filter_values['active'] ?? [];
    $maintained_values = $filter_values['maintained'] ?? [];

    // Sort options.
    $sort = NULL;
    if (!empty($query['sort'])) {
      $sort = $this->mapSortDirection($query['sort']);
      if (in_array($sort, ['ASC', 'DESC'])) {
        $sort = ($sort == 'DESC') ? '-' : '';
        $sort_field = $this->mapSortField($query['sort']);
        $sort = ($sort_field) ? $sort . $sort_field : FALSE;
      }
    }
    $query['sort'] = $sort;

    // Maintenance options.
    $maintenance = NULL;
    if (!empty($query['maintenance_status'])) {
      $maintenance = implode(',', $maintained_values);
    }
    $query['maintenance_status'] = $maintenance;

    // Development options.
    $development = NULL;
    if (!empty($query['development_status'])) {
      $development = implode(',', $active_values);
    }
    $query['development_status'] = $development;

    // Security options.
    $security = NULL;
    if (!empty($query['security_advisory_coverage'])) {
      $security = implode(',', self::COVERED_VALUES);
    }
    $query['security_advisory_coverage'] = $security;

    // Defaults in case none is given.
    $query['page'] = $query['page'] ?? 0;
    $query['limit'] = $query['limit'] ?? 12;

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getSortOptions(): array {
    return [
      'best_match' => [
        'id' => 'best_match',
        'text' => $this->t('Most relevant'),
      ],
      'created' => [
        'id' => 'created',
        'text' => $this->t('Newest first'),
      ],
    ];
  }

}
