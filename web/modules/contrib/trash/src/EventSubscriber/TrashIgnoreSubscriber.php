<?php

declare(strict_types=1);

namespace Drupal\trash\EventSubscriber;

use Drupal\Core\Update\UpdateKernel;
use Drupal\trash\TrashManagerInterface;
use Drupal\workspaces\Event\WorkspacePostPublishEvent;
use Drupal\workspaces\Event\WorkspacePrePublishEvent;
use Drupal\workspaces\Event\WorkspacePublishEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Listens to events where trash context has to be ignored.
 */
class TrashIgnoreSubscriber implements EventSubscriberInterface {

  /**
   * Constructor.
   *
   * @param \Drupal\trash\TrashManagerInterface $trashManager
   *   The trash manager.
   */
  public function __construct(
    protected TrashManagerInterface $trashManager,
  ) {}

  /**
   * Sets the trash context to ignore.
   *
   * This is required so upgrades affecting entities will affect all entities,
   * no matter if they have been trashed.
   *
   * @param \Symfony\Component\HttpKernel\Event\KernelEvent $event
   *   The KernelEvent to process.
   */
  public function onRequest(KernelEvent $event): void {
    if ($event->getKernel() instanceof UpdateKernel) {
      $this->trashManager->setTrashContext('ignore');
    }
  }

  /**
   * Ignores the trash context when publishing a workspace.
   *
   * @param \Drupal\workspaces\Event\WorkspacePublishEvent $event
   *   The workspace publish event.
   */
  public function onWorkspacePrePublish(WorkspacePublishEvent $event): void {
    $this->trashManager->setTrashContext('ignore');
  }

  /**
   * Reverts the trash context after publishing a workspace.
   *
   * @param \Drupal\workspaces\Event\WorkspacePublishEvent $event
   *   The workspace publish event.
   */
  public function onWorkspacePostPublish(WorkspacePublishEvent $event): void {
    $this->trashManager->setTrashContext('active');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::REQUEST][] = ['onRequest'];

    if (class_exists(WorkspacePublishEvent::class)) {
      $events[WorkspacePrePublishEvent::class][] = ['onWorkspacePrePublish'];
      $events[WorkspacePostPublishEvent::class][] = ['onWorkspacePostPublish'];
    }

    return $events;
  }

}
