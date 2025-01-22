<?php

namespace Drupal\trash\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\trash\TrashManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a generic base class for a content entity purge form.
 */
class EntityPurgeForm extends ContentEntityConfirmFormBase {

  /**
   * The trash manager service.
   *
   * @var \Drupal\trash\TrashManagerInterface
   */
  protected TrashManagerInterface $trashManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->trashManager = $container->get('trash.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to permanently delete the @entity-type %label?', [
      '@entity-type' => $this->getEntity()->getEntityType()->getSingularLabel(),
      '%label' => $this->getEntity()->label() ?? $this->getEntity()->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getRedirectUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Trash operations are allowed in a workspace.
    $form_state->set('workspace_safe', TRUE);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();
    $message = $this->t('The @entity-type %label has been permanently deleted.', [
      '@entity-type' => $entity->getEntityType()->getSingularLabel(),
      '%label' => $entity->label() ?? $entity->id(),
    ]);

    $this->trashManager->executeInTrashContext('inactive', function () use ($entity) {
      $entity->delete();
    });
    $form_state->setRedirectUrl($this->getRedirectUrl());

    $this->messenger()->addStatus($message);
  }

  /**
   * Returns the URL where the user should be redirected after purging.
   *
   * @return \Drupal\Core\Url
   *   The redirect URL.
   */
  protected function getRedirectUrl() {
    return Url::fromRoute('trash.admin_content_trash_entity_type', [
      'entity_type_id' => $this->getEntity()->getEntityTypeId(),
    ]);
  }

}
