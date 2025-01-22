<?php

declare(strict_types=1);

namespace Drupal\project_browser;

use Drupal\Core\Url;
use Drupal\project_browser\ProjectBrowser\Project;
use Symfony\Component\HttpFoundation\Response;

/**
 * A generalized activator that can handle any type of project.
 *
 * This is a service collector that tries to delegate to the first registered
 * activator that says it supports a given project.
 */
final class Activator implements ActivatorInterface {

  /**
   * The registered activators.
   *
   * @var \Drupal\project_browser\ActivatorInterface[]
   */
  private array $activators = [];

  /**
   * Registers an activator.
   *
   * @param \Drupal\project_browser\ActivatorInterface $activator
   *   The activator to register.
   */
  public function addActivator(ActivatorInterface $activator): void {
    if (in_array($activator, $this->activators, TRUE)) {
      return;
    }
    $this->activators[] = $activator;
  }

  /**
   * Returns the registered activator to handle a given project.
   *
   * @param \Drupal\project_browser\ProjectBrowser\Project $project
   *   A project object.
   *
   * @return \Drupal\project_browser\ActivatorInterface
   *   The activator which can handle the given project.
   *
   * @throws \InvalidArgumentException
   *   Thrown if none of the registered activators can handle the given project.
   */
  private function getActivatorForProject(Project $project): ActivatorInterface {
    foreach ($this->activators as $activator) {
      if ($activator->supports($project)) {
        return $activator;
      }
    }
    throw new \InvalidArgumentException("The project '$project->machineName' is not supported by any registered activators.");
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(Project $project): ActivationStatus {
    return $this->getActivatorForProject($project)->getStatus($project);
  }

  /**
   * {@inheritdoc}
   */
  public function supports(Project $project): bool {
    try {
      return $this->getActivatorForProject($project) instanceof ActivatorInterface;
    }
    catch (\InvalidArgumentException) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function activate(Project $project): ?Response {
    return $this->getActivatorForProject($project)->activate($project);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstructions(Project $project): string|Url|null {
    return $this->getActivatorForProject($project)->getInstructions($project);
  }

}
