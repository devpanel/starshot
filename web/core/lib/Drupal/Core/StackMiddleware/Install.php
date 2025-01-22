<?php

namespace Drupal\Core\StackMiddleware;

use Drupal\Core\Cache\Cache;
use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\State\StateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Redirects to the installer if install tasks are not done.
 */
class Install implements HttpKernelInterface {

  /**
   * Constructs a new Install instance.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $httpKernel
   *   The wrapped HTTP kernel.
   * @param \Drupal\Core\DrupalKernelInterface $drupalKernel
   *   The main Drupal kernel.
   * @param \Drupal\Core\State\StateInterface $state
   *   The Drupal state service.
   */
  public function __construct(
    protected readonly HttpKernelInterface $httpKernel,
    protected readonly DrupalKernelInterface $drupalKernel,
    protected readonly StateInterface $state,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = TRUE): Response {
    $task = $this->state->get('install_task', 'done');
    if ($task !== 'done') {
      // Caches are not valid until installation is done.
      foreach (Cache::getBins() as $cache_backend) {
        $cache_backend->deleteAll();
      }
      $this->drupalKernel->invalidateContainer();
      $this->state->resetCache();
      // Only redirect if this is an HTML response (i.e., a user trying to view
      // the site in a web browser before installing it).
      $format = $request->query->get(MainContentViewSubscriber::WRAPPER_FORMAT, $request->getRequestFormat());
      if ($format == 'html') {
        return new RedirectResponse($request->getBasePath() . '/core/install.php', 302, ['Cache-Control' => 'no-cache']);
      }
    }
    return $this->httpKernel->handle($request, $type, $catch);
  }

}
