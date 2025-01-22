<?php

declare(strict_types=1);

namespace Drupal\project_browser;

use Drupal\Core\Url;
use Drupal\project_browser\ProjectBrowser\Project;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines an interface for services which can activate projects.
 *
 * An activator is the "source of truth" about the state of a particular project
 * in the current site -- for example, an activator that handles modules knows
 * if the module is already installed.
 */
interface ActivatorInterface {

  /**
   * Determines if a particular project is activated on the current site.
   *
   * @param \Drupal\project_browser\ProjectBrowser\Project $project
   *   A project to check.
   *
   * @return \Drupal\project_browser\ActivationStatus
   *   The state of the project on the current site.
   */
  public function getStatus(Project $project): ActivationStatus;

  /**
   * Determines if this activator can handle a particular project.
   *
   * For example, an activator that handles themes might return TRUE from this
   * method if the project's Composer package type is `drupal-theme`.
   *
   * @param \Drupal\project_browser\ProjectBrowser\Project $project
   *   A project to check.
   *
   * @return bool
   *   TRUE if this activator is responsible for the given project, FALSE
   *   otherwise.
   */
  public function supports(Project $project): bool;

  /**
   * Activates a project on the current site.
   *
   * @param \Drupal\project_browser\ProjectBrowser\Project $project
   *   The project to activate.
   *
   * @return \Symfony\Component\HttpFoundation\Response|null
   *   Optionally, a response that should be presented to the user in Project
   *   Browser. This could be a set of additional instructions to display in a
   *   modal, for example, or a redirect to a configuration form.
   */
  public function activate(Project $project): ?Response;

  /**
   * Returns instructions, if applicable, for how to activate a project.
   *
   * @param \Drupal\project_browser\ProjectBrowser\Project $project
   *   The project to activate.
   *
   * @return string|\Drupal\Core\Url|null
   *   One of:
   *   - A translated string containing human-readable instructions for how to
   *     activate the given project. The UI will display these instructions in
   *     a modal dialog.
   *   - A URL which this project's "Install" button should link to in the UI.
   *   - NULL if instructions are unavailable or unnecessary (for example, if
   *     the project is a module that's already installed).
   */
  public function getInstructions(Project $project): string|Url|null;

}
