<?php

namespace Drupal\trash;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\views\Plugin\ViewsHandlerManager;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\Plugin\views\query\Sql;
use Drupal\views\ViewExecutable;
use Drupal\views\ViewsData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for altering views queries.
 *
 * @internal
 */
class ViewsQueryAlter implements ContainerInjectionInterface {

  /**
   * Constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected TrashManagerInterface $trashManager,
    protected ViewsData $viewsData,
    protected ViewsHandlerManager $joinHandler,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('trash.manager'),
      $container->get('views.views_data'),
      $container->get('plugin.manager.views.join')
    );
  }

  /**
   * Implements a hook bridge for hook_views_query_alter().
   *
   * @see hook_views_query_alter()
   */
  public function alterQuery(QueryPluginBase $query): void {
    // Don't alter any non-sql views queries.
    if (!$query instanceof Sql || !$this->trashManager->shouldAlterQueries()) {
      return;
    }

    // Find out what entity types are represented in this query.
    $entity_type_definitions = $this->entityTypeManager->getDefinitions();
    foreach ($query->relationships as $relationship_table => $info) {
      if (!array_key_exists('base', $info) || !$info['base']) {
        continue;
      }

      $table_data = $this->viewsData->get($info['base']);

      // Skip non-entity tables and entity types without trash integration.
      if (empty($table_data['table']['entity type'])
        || !$this->trashManager->isEntityTypeEnabled($entity_type_definitions[$table_data['table']['entity type']])) {
        continue;
      }
      $entity_type_id = $table_data['table']['entity type'];
      $deleted_table_name = $relationship_table;

      // This is a revision table, we need to join and use the data table which
      // holds the relevant "deleted" state.
      if ($table_data['table']['entity revision']) {
        $id_key = $entity_type_definitions[$entity_type_id]->getKey('id');
        $data_table = $table_data[$id_key]['relationship']['base'];

        $definition = [
          'type' => 'LEFT',
          'table' => $data_table,
          'field' => $id_key,
          'left_table' => $info['base'],
          'left_field' => $id_key,
        ];
        $join = $this->joinHandler->createInstance('standard', $definition);

        $deleted_table_name = $query->addTable($data_table, NULL, $join);
      }

      $this->alterQueryForEntityType($query, $entity_type_id, $deleted_table_name);
    }
  }

  /**
   * Alters the entity type tables for a Views query.
   *
   * Adds a data_table.deleted IS NULL condition unless there is a specific
   * filter for the deleted field already.
   *
   * @param \Drupal\views\Plugin\views\query\Sql $query
   *   The query plugin object for the query.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $deleted_table_name
   *   The name of the data table which holds the 'deleted' column.
   */
  protected function alterQueryForEntityType(Sql $query, string $entity_type_id, string $deleted_table_name): void {
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    assert($storage instanceof SqlEntityStorageInterface);
    $table_mapping = $storage->getTableMapping();
    assert($table_mapping instanceof DefaultTableMapping);
    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);

    $has_delete_condition = FALSE;

    // Try to find out whether any filter (normal or conditional filter) filters
    // by the delete column. In case it does opt out of adding a specific
    // delete column.
    $deleted_table_names = $table_mapping->getAllFieldTableNames('deleted');
    $deleted_table_column = $table_mapping->getFieldColumnName($field_storage_definitions['deleted'], 'value');
    foreach ($query->where as $group) {
      foreach ($group['conditions'] as $condition) {
        // Look through all the tables involved in the query, and check for
        // those that might contain the 'deleted' column, either the data or
        // revision data table.
        foreach ($query->getTableQueue() as $alias => $table_info) {
          if (in_array($table_info['table'], $deleted_table_names, TRUE)) {
            // Note: We use strpos because views for some reason has a field
            // looking like "trash_test.Deleted > 0".
            if (!empty($condition['field']) && strpos($condition['field'], "{$alias}.{$deleted_table_column}") !== FALSE) {
              $has_delete_condition = TRUE;
            }
          }
        }
      }
    }

    // If we couldn't find any condition that filters out explicitly on deleted,
    // ensure that we just return not deleted entities.
    if (!$has_delete_condition) {
      $query->addWhere(0, "{$deleted_table_name}.{$deleted_table_column}", NULL, 'IS NULL');
    }
    // Otherwise ignore trash for the duration of this view, so it can load and
    // display deleted entities.
    else {
      $this->trashManager->setTrashContext('ignore');
      $query->addTag('ignore_trash');
    }
  }

  /**
   * Implements a hook bridge for hook_views_post_render().
   *
   * @see hook_views_post_render()
   */
  public function postRender(ViewExecutable $view): void {
    $query = $view->getQuery();
    if ($query instanceof Sql && in_array('ignore_trash', $query->tags, TRUE)) {
      // Enable trash again after the view has been built.
      $this->trashManager->setTrashContext('active');
    }
  }

}
