<?php

namespace Drupal\project_browser_source_example\Plugin\ProjectBrowserSource;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\project_browser\Plugin\ProjectBrowserSourceBase;
use Drupal\project_browser\ProjectBrowser\Project;
use Drupal\project_browser\ProjectBrowser\ProjectsResultsPage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Project Browser Source Plugin example code.
 *
 * @ProjectBrowserSource(
 *   id = "project_browser_source_example",
 *   label = @Translation("Example source"),
 *   description = @Translation("Example source plugin for Project Browser."),
 * )
 */
class ProjectBrowserSourceExample extends ProjectBrowserSourceBase {

  /**
   * Constructor for example plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request from the browser.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected readonly RequestStack $requestStack,
    protected ModuleExtensionList $moduleExtensionList,
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
      $container->get(ModuleExtensionList::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getProjects(array $query = []): ProjectsResultsPage {
    // Each of the following steps could be done in a separate function. We are
    // including them here for simplicity.
    // --
    // Step 1: Interpret the $query made and adapt it to your needs so you can
    // filter and sort the data accordingly.
    // @see \Drupal\project_browser\Plugin\ProjectBrowserSourceInterface
    $categories_filter = explode(',', $query['categories'] ?? '');
    $machine_name_filter = $query['machine_name'] ?? '';

    // Step 2: Get the projects from wherever your source is and create Project
    // Browser "Project" objects for the final result-set. This could be your
    // own REST endpoint, GraphQL, a Google Spreadsheet, anything! Refer to
    // other plugins if you want more examples. The $query should be taken into
    // account when filtering, but adapted to the way that you obtain the data.
    $request = $this->requestStack->getCurrentRequest();
    $projects_from_source = [
      // The source data can use any keys, we will adapt it later.
      [
        'identifier' => 'p1',
        'unique_name' => 'project_1',
        'label' => 'Project 1',
        'short_description' => 'Quick summary to show in the cards.',
        'long_description' => 'Extended project information to show in the detail page',
        'author' => 'Jane Doe',
        'logo' => $request->getSchemeAndHttpHost() . '/core/misc/logo/drupal-logo.svg',
        'created_at' => strtotime('1 year ago'),
        'updated_at' => strtotime('1 month ago'),
        'categories' => ['cat_1:Category 1'],
        'composer_namespace' => 'my-awesome-namespace/project_1',
      ],
    ];

    // Very basic filtering for this example based on the query made.
    // The filtering itself is done at the source, it can happen before or
    // after, it's up to your source and how it works.
    if (in_array('cat_2', $categories_filter) && !in_array('cat_1', $categories_filter)) {
      array_pop($projects_from_source);
    }
    if (!empty($machine_name_filter) && $machine_name_filter !== 'project_1') {
      array_pop($projects_from_source);
    }

    // Step 3: You MUST set each of the following properties for every
    // project in your result-set. Here is an example of a Project object
    // fully populated.
    $projects = [];
    foreach ($projects_from_source as $project_from_source) {
      // Empty array if no author information is provided.
      $author = [
        'name' => $project_from_source['author'],
      ];

      // Empty array if there is no logo.
      $logo = [
        'file' => [
          // Url of the logo in "https" format.
          'uri' => $project_from_source['logo'],
          'resource' => 'image',
        ],
        'alt' => 'Project 1 logo',
      ];

      // Empty array if there are no categories.
      $categories = [];
      foreach ($project_from_source['categories'] as $category) {
        [$id, $name] = explode(':', $category);
        $categories[] = [
          'id' => $id,
          'name' => $name,
        ];
      }

      $projects[] = new Project(
        logo: $logo,
        // Maybe the source won't have all fields, but we still need to
        // populate the values of all the properties.
        isCompatible: TRUE,
        isMaintained: TRUE,
        isCovered: TRUE,
        machineName: $project_from_source['unique_name'],
        body: [
          'summary' => $project_from_source['short_description'],
          'value' => $project_from_source['long_description'],
        ],
        title: $project_from_source['label'],
        author: $author,
        packageName: $project_from_source['composer_namespace'],
        categories: $categories,
        // Images: Array of images using the same structure as $logo, above.
        images: [],
      );
      $pb_path = $this->moduleExtensionList->getPath('project_browser');
      $projects[] = new Project(
        logo: $logo,
        // Maybe the source won't have all fields, but we still need to
        // populate the values of all the properties.
        isCompatible: TRUE,
        isMaintained: TRUE,
        isCovered: TRUE,
        machineName: $project_from_source['unique_name'] . '2',
        body: [
          'summary' => $project_from_source['short_description'] . ' (different commands)',
          'value' => $project_from_source['long_description'] . ' (different commands)',
        ],
        title: 'A project with different commands',
        author: $author,
        packageName: $project_from_source['composer_namespace'],
        categories: $categories,
        // Images: Array of images using the same structure as $logo, above.
        images: [],
      );
    }

    // Return one page of results. The first parameter is the total number of
    // results for the set, as filtered by $query.
    return $this->createResultsPage($projects);
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories(): array {
    // Step 1: The list of categories that the modules can have. The array
    // must have the "id" and "name" properties defined. If your source data
    // does not use categories then return an empty array. These can be
    // hardcoded, come from REST or GraphQL endpoints, etc.
    return [
      [
        'id' => 'cat_1',
        'name' => 'Category 1',
      ],
      [
        'id' => 'cat_2',
        'name' => 'Category 2',
      ],
    ];
  }

}
