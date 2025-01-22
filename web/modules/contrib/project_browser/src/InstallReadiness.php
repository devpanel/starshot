<?php

namespace Drupal\project_browser;

use Drupal\package_manager\StatusCheckTrait;
use Drupal\project_browser\ComposerInstaller\Installer;
use Drupal\system\SystemManager;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Defines Installer service.
 */
class InstallReadiness {

  use StatusCheckTrait;

  public function __construct(
    private readonly Installer $installer,
    private readonly EventDispatcherInterface $eventDispatcher,
  ) {}

  /**
   * Checks if the environment meets Package Manager install requirements.
   *
   * @return array[]
   *   errors - an array of messages with severity 2
   *   messages - all other messages below severity 2 (warnings)
   */
  public function validatePackageManager() {
    $errors = [];
    $warnings = [];
    foreach ($this->runStatusCheck($this->installer, $this->eventDispatcher) as $result) {
      $messages = $result->messages;
      $summary = $result->summary;
      if ($summary) {
        array_unshift($messages, $summary);
      }
      $text = implode("\n", $messages);

      if ($result->severity === SystemManager::REQUIREMENT_ERROR) {
        $errors[] = $text;
      }
      else {
        $warnings[] = $text;
      }
    }
    return [
      'errors' => $errors,
      'warnings' => $warnings,
    ];
  }

  /**
   * Checks if the installer is available.
   *
   * @return bool
   *   If the installer is currently available.
   */
  public function installerAvailable() {
    return $this->installer->isAvailable();
  }

}
