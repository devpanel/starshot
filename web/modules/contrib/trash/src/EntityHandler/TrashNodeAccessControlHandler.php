<?php

namespace Drupal\trash\EntityHandler;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeAccessControlHandler;

/**
 * Overrides the node access control handler to check Trash access first.
 *
 * @see \Drupal\node\Entity\Node
 * @see \Drupal\node\NodeAccessControlHandler
 * @ingroup node_access
 */
class TrashNodeAccessControlHandler extends NodeAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    $account = $this->prepareUser($account);

    // Invoke the trash access checker before the 'bypass node access'
    // permission is checked by the parent implementation.
    $trash_access = trash_entity_access($entity, $operation, $account);
    if ($trash_access->isForbidden()) {
      return $return_as_object ? $trash_access : $trash_access->isAllowed();
    }

    $result = parent::access($entity, $operation, $account, TRUE);
    assert($result instanceof AccessResult);
    $result->cachePerPermissions();

    return $return_as_object ? $result : $result->isAllowed();
  }

}
