<?php

namespace Drupal\trash;

use Drupal\Core\Entity\RevisionLogInterface;

/**
 * Provides the ability to soft-delete entities at the storage level.
 */
trait TrashStorageTrait {

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
    if ($this->getTrashManager()->getTrashContext() !== 'active') {
      parent::delete($entities);
      return;
    }

    $to_delete = [];
    $to_trash = [];

    foreach ($entities as $entity) {
      if ($this->getTrashManager()->isEntityTypeEnabled($entity->getEntityType(), $entity->bundle())) {
        $to_trash[] = $entity;
      }
      else {
        $to_delete[] = $entity;
      }
    }

    parent::delete($to_delete);

    $field_name = 'deleted';
    $revisionable = $this->getEntityType()->isRevisionable();

    foreach ($to_trash as $entity) {
      // Allow code to run before soft-deleting.
      $this->invokeHook('pre_trash_delete', $entity);

      $entity->set($field_name, \Drupal::time()->getRequestTime());

      // Always create a new revision if the entity type is revisionable.
      if ($revisionable) {
        /** @var \Drupal\Core\Entity\RevisionableInterface $entity */
        $entity->setNewRevision(TRUE);

        if ($entity instanceof RevisionLogInterface) {
          $entity->setRevisionUserId(\Drupal::currentUser()->id());
          $entity->setRevisionCreationTime(\Drupal::time()->getRequestTime());
        }
      }
      $entity->save();

      // Allow code to run after soft-deleting.
      $this->invokeHook('trash_delete', $entity);
    }
  }

  /**
   * Restores soft-deleted entities.
   *
   * @param array $entities
   *   An array of entity objects to restore.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures, an exception is thrown.
   */
  public function restoreFromTrash(array $entities) {
    $field_name = 'deleted';
    $revisionable = $this->getEntityType()->isRevisionable();

    foreach ($entities as $entity) {
      // Allow code to run before restoring from trash.
      $this->invokeHook('pre_trash_restore', $entity);

      $entity->set($field_name, NULL);

      // Always create a new revision if the entity type is revisionable.
      if ($revisionable) {
        /** @var \Drupal\Core\Entity\RevisionableInterface $entity */
        $entity->setNewRevision(TRUE);

        if ($entity instanceof RevisionLogInterface) {
          $entity->setRevisionUserId(\Drupal::currentUser()->id());
          $entity->setRevisionCreationTime(\Drupal::time()->getRequestTime());
        }
      }
      $entity->save();

      // Allow code to run after restoring from trash.
      $this->invokeHook('trash_restore', $entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function buildQuery($ids, $revision_ids = FALSE) {
    $query = parent::buildQuery($ids, $revision_ids);

    if ($this->getTrashManager()->getTrashContext() !== 'active') {
      return $query;
    }

    $table_mapping = $this->getTableMapping();
    $deleted_column = $table_mapping->getFieldColumnName($this->fieldStorageDefinitions['deleted'], 'value');

    // Ensure that entity_load excludes deleted entities.
    if ($revision_ids && ($revision_data = $this->getRevisionDataTable())) {
      $query->join($revision_data, 'revision_data', "[revision_data].[{$this->revisionKey}] = [revision].[{$this->revisionKey}]");
      $query->condition("revision_data.$deleted_column", NULL, 'IS NULL');
    }
    elseif ($revision_ids) {
      $query->condition("revision.$deleted_column", NULL, 'IS NULL');
    }
    elseif ($data_table = $this->getDataTable()) {
      $query->join($data_table, 'data', "[data].[{$this->idKey}] = [base].[{$this->idKey}]");
      $query->condition("data.$deleted_column", NULL, 'IS NULL');
    }
    else {
      $query->condition("base.$deleted_column", NULL, 'IS NULL');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function setPersistentCache($entities) {
    if (!$this->entityType->isPersistentlyCacheable()) {
      return;
    }

    // Ensure that deleted entities are never stored in the persistent cache.
    foreach ($entities as $id => $entity) {
      if (trash_entity_is_deleted($entity)) {
        unset($entities[$id]);
      }
    }

    parent::setPersistentCache($entities);
  }

  /**
   * {@inheritdoc}
   */
  protected function getStorageSchema() {
    if (!isset($this->storageSchema)) {
      $class = $this->entityType->getHandlerClass('storage_schema') ?: 'Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema';

      // Ensure that we use our generated storage schema class.
      $class = _trash_generate_storage_class($class, 'storage_schema');

      $this->storageSchema = new $class($this->entityTypeManager, $this->entityType, $this, $this->database, $this->entityFieldManager);
    }
    return $this->storageSchema;
  }

  /**
   * Gets the trash manager service.
   */
  private function getTrashManager(): TrashManagerInterface {
    return \Drupal::service('trash.manager');
  }

}
