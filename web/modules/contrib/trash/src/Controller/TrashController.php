<?php

namespace Drupal\trash\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\trash\TrashManagerInterface;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Defines a controller to list deleted entities.
 */
class TrashController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Constructs a TrashController object.
   */
  public function __construct(
    protected TrashManagerInterface $trashManager,
    protected EntityTypeBundleInfoInterface $bundleInfo,
    protected DateFormatterInterface $dateFormatter,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('trash.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('date.formatter')
    );
  }

  /**
   * Provides the trash listing page for any entity type.
   *
   * @param string|null $entity_type_id
   *   The ID of the entity type to render.
   *
   * @return array
   *   A render array.
   */
  public function listing(?string $entity_type_id = NULL) : array {
    $enabled_entity_types = $this->trashManager->getEnabledEntityTypes();
    if (empty($enabled_entity_types)) {
      throw new NotFoundHttpException();
    }

    $default_entity_type = in_array('node', $enabled_entity_types, TRUE) ? 'node' : reset($enabled_entity_types);
    $entity_type_id = $entity_type_id ?: $default_entity_type;
    if (!in_array($entity_type_id, $enabled_entity_types, TRUE)) {
      throw new NotFoundHttpException();
    }

    $build = $this->trashManager->executeInTrashContext('inactive', function () use ($entity_type_id) {
      return $this->render($entity_type_id);
    });
    $build['#cache']['tags'][] = 'config:trash.settings';

    return $build;
  }

  /**
   * Builds a listing of deleted entities for the given entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  protected function render(string $entity_type_id): array {
    $entity_type = $this->entityTypeManager()->getDefinition($entity_type_id);
    $build['table'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader($entity_type),
      '#rows' => [],
      '#empty' => $this->t('There are no deleted @label.', ['@label' => $entity_type->getPluralLabel()]),
      '#cache' => [
        'contexts' => $entity_type->getListCacheContexts(),
        'tags' => $entity_type->getListCacheTags(),
      ],
    ];
    foreach ($this->load($entity_type) as $entity) {
      if ($row = $this->buildRow($entity)) {
        $build['table']['#rows'][$entity->id()] = $row;
      }
    }

    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }

  /**
   * Loads entities of this type from storage for listing.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entities implementing \Drupal\Core\Entity\EntityInterface
   *   indexed by their IDs.
   */
  protected function load(EntityTypeInterface $entity_type): array {
    $storage = $this->entityTypeManager()->getStorage($entity_type->id());
    $entity_ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->sort($entity_type->getKey('id'))
      ->pager(50)
      ->execute();
    return $storage->loadMultiple($entity_ids);
  }

  /**
   * Builds the header row for the entity listing.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return array
   *   A render array structure of header strings.
   */
  protected function buildHeader(EntityTypeInterface $entity_type): array {
    $row['label'] = $this->t('Title');
    $row['bundle'] = $entity_type->getBundleLabel();
    if ($entity_type->entityClassImplements(EntityOwnerInterface::class)) {
      $row['owner'] = $this->t('Author');
    }
    if ($entity_type->entityClassImplements(EntityPublishedInterface::class)) {
      $row['published'] = $this->t('Status');
    }
    if ($entity_type->entityClassImplements(RevisionLogInterface::class)) {
      $row['revision_user'] = $this->t('Deleted by');
    }
    $row['deleted'] = $this->t('Deleted on');
    $row['operations'] = $this->t('Operations');
    return $row;
  }

  /**
   * Builds a row for an entity in the entity listing.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity for this row of the list.
   *
   * @return array
   *   A render array structure of fields for this entity.
   */
  protected function buildRow(FieldableEntityInterface $entity): array {
    $entity_type = $entity->getEntityType();
    if ($entity_type->getLinkTemplate('canonical') != $entity_type->getLinkTemplate('edit-form')) {
      $row['label']['data'] = [
        '#type' => 'link',
        '#title' => "{$entity->label()} ({$entity->id()})",
        '#url' => $entity->toUrl('canonical', ['query' => ['in_trash' => TRUE]]),
      ];
    }
    else {
      $row['label']['data'] = [
        '#markup' => "{$entity->label()} ({$entity->id()})",
      ];
    }

    $row['bundle'] = $this->bundleInfo->getBundleInfo($entity->getEntityTypeId())[$entity->bundle()]['label'];

    if ($entity_type->entityClassImplements(EntityOwnerInterface::class)) {
      assert($entity instanceof EntityOwnerInterface);
      $row['owner']['data'] = [
        '#theme' => 'username',
        '#account' => $entity->getOwner(),
      ];
    }

    if ($entity_type->entityClassImplements(EntityPublishedInterface::class)) {
      assert($entity instanceof EntityPublishedInterface);
      $row['published'] = $entity->isPublished() ? $this->t('published') : $this->t('not published');
    }

    if ($entity_type->entityClassImplements(RevisionLogInterface::class)) {
      assert($entity instanceof RevisionLogInterface);
      $row['revision_user']['data'] = [
        '#theme' => 'username',
        '#account' => $entity->getRevisionUser(),
      ];
    }

    $row['deleted'] = $this->dateFormatter->format($entity->get('deleted')->value, 'short');

    $row['operations']['data'] = [
      '#type' => 'operations',
      '#links' => [
        'restore' => [
          'title' => t('Restore'),
          'url' => $entity->toUrl('restore'),
          'weight' => 0,
        ],
        'purge' => [
          'title' => t('Purge'),
          'url' => $entity->toUrl('purge'),
          'weight' => 5,
        ],
      ],
    ];

    return $row;
  }

}
