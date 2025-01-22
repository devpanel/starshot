<?php

namespace Drupal\Tests\trash\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\trash\TrashManagerInterface;

/**
 * Base class for Trash kernel tests.
 */
abstract class TrashKernelTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'image',
    'node',
    'media',
    'trash',
    'trash_test',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['trash_test']);
    $this->installEntitySchema('file');
    $this->installEntitySchema('node');
    $this->installEntitySchema('media');
    $this->installEntitySchema('user');
    $this->installEntitySchema('trash_test_entity');

    $config = \Drupal::configFactory()->getEditable('trash.settings');
    $enabled_entity_types = $config->get('enabled_entity_types');
    $enabled_entity_types['trash_test_entity'] = [];
    $enabled_entity_types['node'] = ['article'];
    $config->set('enabled_entity_types', $enabled_entity_types);
    $config->save();
  }

  /**
   * Gets the trash manager.
   */
  protected function getTrashManager(): TrashManagerInterface {
    return \Drupal::service('trash.manager');
  }

}
