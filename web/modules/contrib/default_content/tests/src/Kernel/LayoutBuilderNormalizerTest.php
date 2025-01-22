<?php

namespace Drupal\Tests\default_content\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;

/**
 * Tests normalizer for content that uses layout builder.
 *
 * @requires module layout_builder
 * @coversDefaultClass \Drupal\default_content\Normalizer\ContentEntityNormalizer
 * @group default_content
 */
class LayoutBuilderNormalizerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'default_content',
    'entity_test',
    'field',
    'layout_builder',
    'layout_discovery',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');

    LayoutBuilderEntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ])
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();
  }

  /**
   * Tests normalizing and denormalizing a layout builder display.
   */
  public function testLayoutBuilderNormalizer() {
    $section_data = [
      new Section('layout_onecol', [
        'label' => 'One column',
      ], [
        '10000000-0000-1000-a000-000000000000' => new SectionComponent('10000000-0000-1000-a000-000000000000', 'content', ['id' => 'foo']),
      ]),
    ];
    $entity = EntityTest::create([OverridesSectionStorage::FIELD_NAME => $section_data]);
    $entity->save();

    /** @var \Drupal\default_content\Normalizer\ContentEntityNormalizerInterface $normalizer */
    $normalizer = \Drupal::service('default_content.content_entity_normalizer');
    $normalized = $normalizer->normalize($entity);
    $expected = [
      '_meta' => [
        'version' => '1.0',
        'entity_type' => 'entity_test',
        'uuid' => $entity->uuid(),
        'bundle' => 'entity_test',
        'default_langcode' => 'en',
      ],
      'default' => [
        OverridesSectionStorage::FIELD_NAME => [
          [
            'section' => [
              'layout_id' => 'layout_onecol',
              'layout_settings' => [
                'label' => 'One column',
              ],
              'components' => [
                '10000000-0000-1000-a000-000000000000' => [
                  'uuid' => '10000000-0000-1000-a000-000000000000',
                  'weight' => 0,
                  'additional' => [],
                  'region' => 'content',
                  'configuration' => [
                    'id' => 'foo',
                  ],
                ],
              ],
              'third_party_settings' => [],
            ],
          ],
        ],
        'created' => [
          0 => [
            'value' => $entity->created->value,
          ],
        ],
        'user_id' => [
          0 => [
            'target_id' => $entity->user_id->target_id,
          ],
        ],
      ],
    ];
    $this->assertEquals($expected, $normalized);

    // Recreate the display from the normalized data.
    $entity->delete();
    $recreated = $normalizer->denormalize($normalized);
    $recreated->save();

    $this->assertEquals($entity->{OverridesSectionStorage::FIELD_NAME}->getValue(), $recreated->{OverridesSectionStorage::FIELD_NAME}->getValue());
  }

}
