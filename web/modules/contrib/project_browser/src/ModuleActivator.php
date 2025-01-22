<?php

declare(strict_types=1);

namespace Drupal\project_browser;

use Composer\InstalledVersions;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\project_browser\ProjectBrowser\Project;
use Symfony\Component\HttpFoundation\Response;

/**
 * An activator for Drupal modules.
 */
final class ModuleActivator implements ActivatorInterface {

  use ActivationInstructionsTrait {
    __construct as traitConstruct;
  }

  public function __construct(
    private readonly ModuleInstallerInterface $moduleInstaller,
    ModuleExtensionList $moduleList,
    FileUrlGeneratorInterface $fileUrlGenerator,
  ) {
    $this->traitConstruct($moduleList, $fileUrlGenerator);
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(Project $project): ActivationStatus {
    if (array_key_exists($project->machineName, $this->moduleList->getAllInstalledInfo())) {
      return ActivationStatus::Active;
    }
    elseif (array_key_exists($project->machineName, $this->moduleList->getAllAvailableInfo())) {
      return ActivationStatus::Present;
    }
    return ActivationStatus::Absent;
  }

  /**
   * {@inheritdoc}
   */
  public function supports(Project $project): bool {
    return $project->type === ProjectType::Module;
  }

  /**
   * {@inheritdoc}
   */
  public function activate(Project $project): ?Response {
    $this->moduleInstaller->install([$project->machineName]);
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstructions(Project $project): string|Url|null {
    if ($this->getStatus($project) === ActivationStatus::Present) {
      return Url::fromRoute('system.modules_list', options: [
        'fragment' => 'module-' . str_replace('_', '-', $project->machineName),
      ]);
    }

    $commands = '<h3>' . $this->t('1. Download') . '</h3>';
    $commands .= '<p>';
    $commands .= $this->t('The <a href="@use" target="_blank" rel="noreferrer noopener">recommended way</a> to download any Drupal module is with <a href="@get" target="_blank" rel="noreferrer noopener">Composer</a>.', [
      '@use' => 'https://www.drupal.org/docs/develop/using-composer/using-composer-to-install-drupal-and-manage-dependencies#managing-contributed',
      '@get' => 'https://getcomposer.org',
    ]);
    $commands .= '</p>';
    $commands .= '<p>' . $this->t("If you already manage your Drupal application dependencies with Composer, run the following from the command line in your application's Composer root directory:") . '</p>';
    $commands .= $this->commandBox('composer require ' . $project->packageName, 'download');
    $commands .= '<p>' . $this->t('This will download the module to your codebase.') . '</p>';
    $commands .= '<p>';
    $commands .= $this->t('Didn\'t work? <a href="@url" target="_blank" rel="noreferrer noopener">Learn how to troubleshoot Composer</a>.', [
      '@url' => 'https://getcomposer.org/doc/articles/troubleshooting.md',
    ]);
    $commands .= '</p>';
    $commands .= '<h3>' . $this->t('2. Install') . '</h3>';
    $commands .= '<p>';
    $commands .= $this->t('Go to the <a href="@url" target="_blank" rel="noreferrer noopener">Extend page</a> (admin/modules), check the box next to each module you wish to enable, then click the Install button at the bottom of the page.', [
      '@url' => Url::fromRoute('system.modules_list')->toString(),
    ]);
    $commands .= '</p>';
    $commands .= '<p>';
    $commands .= $this->t('Alternatively, you can use <a href="@url" target="_blank" rel="noreferrer noopener">Drush</a> to install it via the command line.', [
      '@url' => 'https://www.drush.org/latest',
    ]);
    $commands .= '</p>';

    $command = '';
    // Only show the command to install Drush if necessary.
    if (!in_array('drush/drush', InstalledVersions::getInstalledPackages(), TRUE)) {
      $command .= "composer require drush/drush\n";
    }
    $command .= 'drush install ' . $project->machineName;

    $commands .= $this->commandBox($command, 'install');
    return $commands;
  }

}
