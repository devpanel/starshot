<?php

namespace Drupal\project_browser;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\project_browser\Plugin\ProjectBrowserSourceInterface;
use Drupal\project_browser\Plugin\ProjectBrowserSourceManager;
use Drupal\project_browser\ProjectBrowser\Project;
use Drupal\project_browser\ProjectBrowser\ProjectsResultsPage;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines enabled source.
 */
class EnabledSourceHandler implements LoggerAwareInterface, EventSubscriberInterface {

  use LoggerAwareTrait;

  /**
   * The key-value storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  private readonly KeyValueStoreExpirableInterface $keyValue;

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly ProjectBrowserSourceManager $pluginManager,
    private readonly ActivatorInterface $activator,
    KeyValueExpirableFactoryInterface $keyValueFactory,
  ) {
    $this->keyValue = $keyValueFactory->get('project_browser');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ConfigEvents::SAVE => 'onConfigSave',
    ];
  }

  /**
   * Reacts when config is saved.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The event object.
   */
  public function onConfigSave(ConfigCrudEvent $event): void {
    if ($event->getConfig()->getName() === 'project_browser.admin_settings' && $event->isChanged('enabled_sources')) {
      $this->keyValue->deleteAll();
    }
  }

  /**
   * Returns all plugin instances corresponding to the enabled_source config.
   *
   * @return \Drupal\project_browser\Plugin\ProjectBrowserSourceInterface[]
   *   Array of plugin instances.
   */
  public function getCurrentSources(): array {
    $plugin_instances = [];
    $config = $this->configFactory->get('project_browser.admin_settings');

    $plugin_ids = $config->get('enabled_sources');
    foreach ($plugin_ids as $plugin_id) {
      if (!$this->pluginManager->hasDefinition($plugin_id)) {
        // Ignore if the plugin does not exist, but log it.
        $this->logger?->warning('Project browser tried to load the enabled source %source, but the plugin does not exist. Make sure you have run update.php after updating the Project Browser module.', ['%source' => $plugin_id]);
      }
      else {
        $plugin_instances[$plugin_id] = $this->pluginManager->createInstance($plugin_id);
      }
    }

    return $plugin_instances;
  }

  /**
   * Returns projects that match a particular query, from specified source.
   *
   * @param string $source_id
   *   The ID of the source plugin to query projects from.
   * @param array $query
   *   (optional) The query to pass to the specified source.
   *
   * @return \Drupal\project_browser\ProjectBrowser\ProjectsResultsPage[]
   *   The result of the query, keyed by source plugin ID.
   */
  public function getProjects(string $source_id, array $query = []): array {
    // Cache only exact query, down to the page number.
    $cache_key = 'query:' . md5(Json::encode($query));

    $stored = $this->keyValue->get($cache_key);
    if (is_array($stored)) {
      // We store query results as a set of arguments to ProjectsResultsPage,
      // although the list of projects is a list of project IDs, all of which
      // we expect to be in the data store.
      $arguments = $stored[$source_id];
      $arguments[1] = array_map($this->getStoredProject(...), $arguments[1]);
      $projects = [
        $source_id => new ProjectsResultsPage(...$arguments),
      ];
    }
    else {
      $projects = $this->doQuery($source_id, $query);

      if ($projects) {
        $results = $projects[$source_id];

        foreach ($results->list as $project) {
          // Prefix the local project ID with the source plugin ID, so we can
          // look it up unambiguously.
          $project->id = $source_id . '/' . $project->id;

          $this->keyValue->setIfNotExists($project->id, $project);
          // Add activation data to the project. This is volatile and should not
          // be changed.
          $this->getActivationData($project);
        }
        // Store each source's results for this query as a set of arguments to
        // ProjectsResultsPage.
        $stored = [
          $source_id => [
            $results->totalResults,
            array_column($results->list, 'id'),
            $results->pluginLabel,
            $source_id,
            $results->error,
          ],
        ];
        $this->keyValue->set($cache_key, $stored);
      }
    }
    return $projects;
  }

  /**
   * Queries the specified source.
   *
   * @param string $source_id
   *   The ID of the source plugin to query projects from.
   * @param array $query
   *   (optional) The query to pass to the specified source.
   *
   * @return \Drupal\project_browser\ProjectBrowser\ProjectsResultsPage[]
   *   The results of the query, keyed by source plugin ID.
   *
   * @see \Drupal\project_browser\Plugin\ProjectBrowserSourceInterface::getProjects()
   */
  private function doQuery(string $source_id, array $query = []): array {
    $query['categories'] ??= '';

    $tabwise_categories = Json::decode($query['tabwise_categories'] ?? '[]');
    unset($query['tabwise_categories']);

    $source = $this->getCurrentSources()[$source_id];
    $query['categories'] = implode(", ", $tabwise_categories[$source_id] ?? []);

    return [$source_id => $source->getProjects($query)];
  }

  /**
   * Returns the available categories across all enabled sources.
   *
   * @return array[]
   *   The available categories, keyed by source plugin ID.
   */
  public function getCategories(): array {
    $cache_key = 'categories';
    $categories = $this->keyValue->get($cache_key);

    if ($categories === NULL) {
      $categories = array_map(
        fn (ProjectBrowserSourceInterface $source) => $source->getCategories(),
        $this->getCurrentSources(),
      );
      $this->keyValue->set($cache_key, $categories);
    }
    return $categories;
  }

  /**
   * Looks up a previously stored project by its ID.
   *
   * @param string $id
   *   The project ID. See ::getProjects() for where this is set.
   *
   * @return \Drupal\project_browser\ProjectBrowser\Project
   *   The project object, with activation status and commands added.
   *
   * @throws \RuntimeException
   *   Thrown if the project is not found in the non-volatile data store.
   */
  public function getStoredProject(string $id): Project {
    $project = $this->keyValue->get($id) ?? throw new \RuntimeException("Project '$id' was not found in non-volatile storage.");
    $this->getActivationData($project);
    return $project;
  }

  /**
   * Adds activation data to a project object.
   *
   * @param \Drupal\project_browser\ProjectBrowser\Project $project
   *   The project object.
   */
  private function getActivationData(Project $project): void {
    // The project's activator is the source of truth about the status of
    // the project with respect to the current site.
    $project->status = $this->activator->getStatus($project);
    // The activator is responsible for generating the instructions.
    $project->commands = $this->activator->getInstructions($project);
    // Give the front-end the ID of the source plugin that exposed this project.
    [$project->source] = explode('/', $project->id, 2);
  }

  /**
   * Clears the key-value store so it can be re-fetched.
   */
  public function clearStorage(): void {
    $this->keyValue->deleteAll();
  }

}
