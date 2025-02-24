<?php

namespace Drush\Commands;

/**
 * Drush commands used during installation.
 */
final class DrupalForgeInstallerCommands extends DrushCommands {

  /**
   * Notifies Drush that installation is being attempted.
   */
  public function __construct() {
    $GLOBALS['install_state']['theme'] = 'drupal_cms_installer_theme';
  }

}
