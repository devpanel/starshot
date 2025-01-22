<?php

namespace Drupal\trash;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\workspaces\WorkspaceInformationInterface;
use Drupal\workspaces\WorkspaceInterface;

@class_alias('Drupal\wse\Core\WorkspaceInformationInterface', 'Drupal\workspaces\WorkspaceInformationInterface');

/**
 * Provides an override for core's workspace association service.
 */
class TrashWorkspaceInformation implements WorkspaceInformationInterface {

  /**
   * Constructor.
   */
  public function __construct(
    protected WorkspaceInformationInterface $inner,
    protected TrashManagerInterface $trashManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function isEntitySupported(EntityInterface $entity): bool {
    return $this->inner->isEntitySupported($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function isEntityTypeSupported(EntityTypeInterface $entity_type): bool {
    return $this->inner->isEntityTypeSupported($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedEntityTypes(): array {
    return $this->inner->getSupportedEntityTypes();
  }

  /**
   * {@inheritdoc}
   */
  public function isEntityIgnored(EntityInterface $entity): bool {
    return $this->inner->isEntityIgnored($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function isEntityTypeIgnored(EntityTypeInterface $entity_type): bool {
    return $this->inner->isEntityTypeIgnored($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function isEntityDeletable(EntityInterface $entity, WorkspaceInterface $workspace): bool {
    if ($this->trashManager->isEntityTypeEnabled($entity->getEntityType(), $entity->bundle())) {
      return TRUE;
    }

    return $this->inner->isEntityDeletable($entity, $workspace);
  }

}
