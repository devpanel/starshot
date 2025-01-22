<?php

namespace Drupal\project_browser\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\project_browser\ProjectBrowser\ProjectsResultsPage;

/**
 * Defines an interface for a Project Browser source.
 *
 * @see \Drupal\project_browser\Annotation\ProjectBrowserSource
 * @see \Drupal\project_browser\Plugin\ProjectBrowserSourceManager
 * @see plugin_api
 */
interface ProjectBrowserSourceInterface extends PluginInspectionInterface {

  /**
   * Gets all the projects available from this source.
   *
   * @param array $query
   *   The query string params from the frontend request.
   *
   *   The expected parameters will be:
   *   - page: Page number.
   *   - limit: Number of elements per page.
   *   - sort: Field to do the sorting on.
   *   - direction: 'ASC' or 'DESC'.
   *   - search: Search term.
   *   - categories: Comma separated list of IDs.
   *   - maintenance_status: Comma separated list of IDs.
   *   - development_status: Comma separated list of IDs.
   *   - security_advisory_coverage: Comma separated list of IDs.
   *   - machine_name: Project's machine name.
   *
   * @return \Drupal\project_browser\ProjectBrowser\ProjectsResultsPage
   *   Returns a \Drupal\project_browser\ProjectBrowser\ProjectsResultsPage.
   */
  public function getProjects(array $query = []): ProjectsResultsPage;

  /**
   * Gets a list of all available categories.
   *
   * @return array
   *   List of categories.
   */
  public function getCategories(): array;

  /**
   * Defines the filters that this source will respect.
   *
   * @return \Drupal\project_browser\ProjectBrowser\Filter\FilterBase[]
   *   The filters that this source will respect when querying for projects,
   *   keyed by machine name.
   */
  public function getFilterDefinitions(): array;

  /**
   * Returns the available sort options that plugins will parse.
   *
   * @return array
   *   List of sort options.
   */
  public function getSortOptions(): array;

}
