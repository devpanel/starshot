<?php

namespace Drupal\project_browser\Drush\Commands;

use Drupal\project_browser\EnabledSourceHandler;
use Drush\Attributes\Command;
use Drush\Attributes\Usage;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;

/**
 * Contains Drush commands for Project Browser.
 */
final class ProjectBrowserCommands extends DrushCommands {

  use AutowireTrait;

  public function __construct(
    private readonly EnabledSourceHandler $enabledSourceHandler,
  ) {
    parent::__construct();
  }

  /**
   * Clears stored project data.
   */
  #[Command(name: 'project-browser:storage-clear', aliases: ['pb-sc'])]
  #[Usage(name: 'project-browser:storage-clear', description: 'Clear stored Project Browser data')]
  public function storageClear(): void {
    $this->enabledSourceHandler->clearStorage();
    $this->logger()->success(dt('Stored data from Project Browser sources have been cleared.'));
  }

}
