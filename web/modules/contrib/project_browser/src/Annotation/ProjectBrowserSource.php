<?php

namespace Drupal\project_browser\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Project Browser source plugin annotation object.
 *
 * Project Browser sources are used to provide information about
 * available projects that can be installed on a Drupal site.
 * Typically, these come from Drupal.org, but may also come
 * from a private repository, etc.
 *
 * Plugin Namespace: Plugin\ProjectBrowserSource
 *
 * For a working example, see:
 * \Drupal\project_browser\Plugin\ProjectBrowserSource\MockDrupalDotOrg
 *
 * @see \Drupal\project_browser\Plugin\ProjectBrowserSourceInterface
 * @see \Drupal\project_browser\Plugin\ProjectBrowserSourceManager
 * @see plugin_api
 *
 * @Annotation
 */
class ProjectBrowserSource extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the source.
   *
   * @var \Drupal\Core\Annotation\Translation
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A short description of the source.
   *
   * @var \Drupal\Core\Annotation\Translation
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The local task definition at which this source should be exposed.
   *
   * If NULL, the source will never be exposed as a local task.
   *
   * @var array|null
   */
  public ?array $local_task = NULL;

}
