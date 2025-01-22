<?php

namespace Drupal\project_browser\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a Project Browser Source Manager.
 *
 * @see \Drupal\project_browser\Annotation\ProjectBrowserSource
 * @see \Drupal\project_browser\Plugin\ProjectBrowserSourceInterface
 * @see plugin_api
 */
class ProjectBrowserSourceManager extends DefaultPluginManager {

  /**
   * Constructs a ProjectBrowserSourceManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/ProjectBrowserSource',
      $namespaces,
      $module_handler,
      'Drupal\project_browser\Plugin\ProjectBrowserSourceInterface',
      'Drupal\project_browser\Annotation\ProjectBrowserSource',
    );

    $this->alterInfo('project_browser_source_info');
    $this->setCacheBackend($cache_backend, 'project_browser_source_info_plugins');
  }

}
