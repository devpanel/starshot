<?php

namespace Drupal\project_browser\Controller;

use Drupal\Core\Controller\ControllerBase;

// cspell:ignore ctools

/**
 * Defines a controller to provide the Project Browser UI.
 *
 * @internal
 *   Controller classes are internal.
 */
class BrowserController extends ControllerBase {

  /**
   * Builds the browse page and the individual module page.
   *
   * For routes without any module name, default browse page is rendered with
   * all the available modules.
   * For example, 'https//drupal-site/admin/modules/browse'.
   * And for module specific paths, the respective detailed module page is
   * rendered. For example, 'https//drupal-site/admin/modules/browse/ctools'
   * will display the details for ctools.
   *
   * @param string $source
   *   The ID of the source plugin to query for projects.
   *
   * @return array
   *   A render array.
   */
  public function browse(string $source): array {
    return [
      '#type' => 'project_browser',
      '#source' => $source,
    ];
  }

}
