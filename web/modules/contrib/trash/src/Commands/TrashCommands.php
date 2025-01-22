<?php

namespace Drupal\trash\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\trash\TrashManagerInterface;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for the Trash module.
 */
class TrashCommands extends DrushCommands {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TrashManagerInterface $trashManager,
  ) {
    parent::__construct();
  }

  /**
   * Purges all trashed entities.
   *
   * @param string|null $entityTypeId
   *   The entity type to purge.
   *
   * @command trash:purge
   * @aliases tp
   */
  public function purge(?string $entityTypeId = NULL): void {
    if (is_string($entityTypeId)) {
      $entityTypeIds = [$entityTypeId];
    }
    else {
      $entityTypeIds = $this->trashManager->getEnabledEntityTypes();
    }

    $deleteCount = 0;
    foreach ($entityTypeIds as $entityTypeId) {
      $storage = $this->entityTypeManager->getStorage($entityTypeId);
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->addMetaData('trash', 'inactive')
        ->exists('deleted')
        ->execute();

      if ($ids === []) {
        continue;
      }

      $this->io()->progressStart(count($ids));
      $chunkSize = Settings::get('entity_update_batch_size', 50);

      foreach (array_chunk($ids, $chunkSize) as $chunk) {
        $this->trashManager->executeInTrashContext('inactive', function () use (&$deleteCount, $storage, $chunk) {
          $entities = $storage->loadMultiple($chunk);
          $storage->delete($entities);
          $deleteCount += count($entities);
          $this->io()->progressAdvance(count($chunk));
        });
      }

      $this->io()->progressFinish();
    }

    if ($deleteCount > 0) {
      $this->io()->success(sprintf('Purged %d trashed entities.', $deleteCount));
    }
    else {
      $this->io()->success('No trashed entities to purge.');
    }
  }

}
