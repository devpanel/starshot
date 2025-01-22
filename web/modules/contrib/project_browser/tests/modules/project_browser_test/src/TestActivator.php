<?php

declare(strict_types=1);

namespace Drupal\project_browser_test;

use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\project_browser\ActivationStatus;
use Drupal\project_browser\ActivatorInterface;
use Drupal\project_browser\ProjectBrowser\Project;
use Symfony\Component\HttpFoundation\Response;

/**
 * A test activator that simply logs a state message.
 */
class TestActivator implements ActivatorInterface {

  public function __construct(
    private readonly ActivatorInterface $decorated,
    private readonly StateInterface $state,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function supports(Project $project): bool {
    return $this->decorated->supports($project);
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(Project $project): ActivationStatus {
    if ($project->machineName === 'pinky_brain') {
      return ActivationStatus::Present;
    }
    return $this->decorated->getStatus($project);
  }

  /**
   * {@inheritdoc}
   */
  public function activate(Project $project): ?Response {
    $log_message = $this->state->get("test activator", []);
    $log_message[] = "$project->title was activated!";
    $this->state->set("test activator", $log_message);
    return $this->decorated->activate($project);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstructions(Project $project): string|Url|null {
    return $this->decorated->getInstructions($project);
  }

}
