<?php

namespace Drupal\project_browser\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\project_browser\EnabledSourceHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for the proxy layer.
 */
class ProjectBrowserEndpointController extends ControllerBase {

  /**
   * Constructor for endpoint controller.
   *
   * @param \Drupal\project_browser\EnabledSourceHandler $enabledSource
   *   The enabled project browser source.
   */
  public function __construct(
    private readonly EnabledSourceHandler $enabledSource,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get(EnabledSourceHandler::class),
    );
  }

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Typically a project listing.
   */
  public function getAllProjects(Request $request) {
    $id = $request->query->get('id');
    if ($id) {
      return new JsonResponse($this->enabledSource->getStoredProject($id));
    }

    $current_sources = $this->enabledSource->getCurrentSources();
    $query = $this->buildQuery($request);
    if (!$current_sources || empty($query['source'])) {
      return new JsonResponse([], Response::HTTP_ACCEPTED);
    }
    return new JsonResponse($this->enabledSource->getProjects($query['source'], $query));
  }

  /**
   * Builds the query based on the current request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   See \Drupal\project_browser\EnabledSourceHandler::getProjects().
   */
  private function buildQuery(Request $request): array {
    // Validate and build query.
    $query = [
      'page' => (int) $request->query->get('page', 0),
      'limit' => (int) $request->query->get('limit', 12),
    ];

    $machine_name = $request->query->get('machine_name');
    if ($machine_name) {
      $query['machine_name'] = $machine_name;
    }

    $sort = $request->query->get('sort');
    if ($sort) {
      $query['sort'] = $sort;
    }

    $title = $request->query->get('search');
    if ($title) {
      $query['search'] = $title;
    }

    $categories = $request->query->get('categories');
    if ($categories) {
      $query['categories'] = $categories;
    }

    $maintenance_status = $request->query->get('maintenance_status');
    if ($maintenance_status) {
      $query['maintenance_status'] = $maintenance_status;
    }

    $development_status = $request->query->get('development_status');
    if ($development_status) {
      $query['development_status'] = $development_status;
    }

    $security_advisory_coverage = $request->query->get('security_advisory_coverage');
    if ($security_advisory_coverage) {
      $query['security_advisory_coverage'] = $security_advisory_coverage;
    }

    $displayed_source = $request->query->get('source', 0);
    if ($displayed_source) {
      $query['source'] = $displayed_source;
    }
    // Done to cache results.
    $tabwise_categories = $request->query->get('tabwise_categories');
    if ($tabwise_categories) {
      $query['tabwise_categories'] = $tabwise_categories;
    }

    return $query;
  }

  /**
   * Returns a list of categories.
   */
  public function getAllCategories() {
    $current_sources = $this->enabledSource->getCurrentSources();
    if (!$current_sources) {
      return new JsonResponse([], Response::HTTP_ACCEPTED);
    }

    return new JsonResponse($this->enabledSource->getCategories());
  }

}
