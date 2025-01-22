<?php

namespace Drupal\project_browser_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns a test page for Project Browser.
 */
class TestPageController extends ControllerBase {

  /**
   * Renders the Project Browser test page.
   *
   * @param string $source
   *   The ID of the source plugin to query for projects.
   *
   * @return array
   *   A render array.
   */
  public function render(string $source): array {
    return [
      '#type' => 'project_browser',
      '#max_selections' => 2,
      '#source' => $source,
    ];
  }

}
