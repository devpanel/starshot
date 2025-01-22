<?php

declare(strict_types=1);

namespace Drupal\trash\Routing;

use Drupal\Core\Routing\EnhancerInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\trash\TrashManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Sets the trash context for entity routes.
 */
class RouteEnhancer implements EnhancerInterface {

  public function __construct(
    protected AccountInterface $currentUser,
    protected TrashManagerInterface $trashManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request): array {
    if ($this->applies($defaults[RouteObjectInterface::ROUTE_OBJECT], $request)) {
      $this->trashManager->setTrashContext('inactive');
    }

    return $defaults;
  }

  /**
   * Determines whether the enhancer should run on the current route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The current route.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request instance.
   *
   * @return bool
   *   TRUE if the enhancer should run, FALSE otherwise.
   */
  protected function applies(Route $route, Request $request): bool {
    $is_trash_route = (bool) $route->getOption('_trash_route');
    $has_trash_query = $request->query->has('in_trash');

    return ($is_trash_route || $has_trash_query) && $this->currentUser->hasPermission('view deleted entities');
  }

}
