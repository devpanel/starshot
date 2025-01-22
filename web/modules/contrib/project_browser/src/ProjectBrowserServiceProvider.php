<?php

namespace Drupal\project_browser;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\PrivateKey;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Recipe\Recipe;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\project_browser\ComposerInstaller\Installer;
use Drupal\project_browser\ComposerInstaller\Validator\CoreNotUpdatedValidator;
use Drupal\project_browser\ComposerInstaller\Validator\PackageNotInstalledValidator;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Base class acts as a helper for Project Browser services.
 */
class ProjectBrowserServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if (array_key_exists('package_manager', $container->getParameter('container.modules'))) {
      parent::register($container);

      $container->register(Installer::class, Installer::class)
        ->setAutowired(TRUE);

      $container->register(InstallState::class, InstallState::class)
        ->setArgument('$keyValueFactory', new Reference('keyvalue'))
        ->setAutowired(TRUE);

      $container->register(InstallReadiness::class, InstallReadiness::class)
        ->setAutowired(TRUE);

      $container->register(CoreNotUpdatedValidator::class, CoreNotUpdatedValidator::class)
        ->addTag('event_subscriber')
        ->setAutowired(TRUE);

      $container->register(PackageNotInstalledValidator::class, PackageNotInstalledValidator::class)
        ->addTag('event_subscriber')
        ->setAutowired(TRUE);
    }

    if (class_exists(Recipe::class)) {
      $container->register(RecipeActivator::class, RecipeActivator::class)
        ->setAutowired(TRUE)
        ->setArgument('$appRoot', new Parameter('app.root'))
        ->addTag('project_browser.activator')
        // Because it's an event subscriber, the activator needs to be public.
        ->addTag('event_subscriber');
    }

    // @todo Remove the following Drupal 10.0 autowiring shim in
    //   https://www.drupal.org/i/3349193.
    $autowire_aliases = [
      ConfigFactoryInterface::class => 'config.factory',
      QueueInterface::class => 'queue',
      ModuleHandlerInterface::class => 'module_handler',
      StateInterface::class => 'state',
      ModuleExtensionList::class => 'extension.list.module',
      ThemeExtensionList::class => 'extension.list.theme',
      StreamWrapperManagerInterface::class => 'stream_wrapper_manager',
      Connection::class => 'database',
      QueueFactory::class => 'queue',
      PrivateKey::class => 'private_key',
    ];
    foreach ($autowire_aliases as $interface => $service_id) {
      if (!$container->hasAlias($interface)) {
        $container->setAlias($interface, $service_id);
      }
    }
  }

}
