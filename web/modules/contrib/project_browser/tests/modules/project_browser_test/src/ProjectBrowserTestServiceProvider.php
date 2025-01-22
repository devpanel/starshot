<?php

namespace Drupal\project_browser_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\project_browser\InstallReadiness;

/**
 * Overrides the module installer service.
 */
class ProjectBrowserTestServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('module_installer');
    $definition->setClass('Drupal\project_browser_test\Extension\TestModuleInstaller')
      ->setLazy(FALSE);

    // The InstallReadiness service is defined by ProjectBrowserServiceProvider
    // if Package Manager is installed.
    if ($container->hasDefinition(InstallReadiness::class)) {
      $container->register(TestInstallReadiness::class, TestInstallReadiness::class)
        ->setAutowired(TRUE)
        ->addTag('event_subscriber');
    }
  }

}
