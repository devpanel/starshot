<?php

declare(strict_types=1);

namespace Drupal\Tests\project_browser\Unit;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;
use Drupal\project_browser\ProjectBrowser\Filter\BooleanFilter;
use Drupal\project_browser\ProjectBrowser\Filter\MultipleChoiceFilter;

/**
 * Tests filters that can be defined by source plugins.
 *
 * @group project_browser
 * @covers \Drupal\project_browser\ProjectBrowser\Filter\BooleanFilter
 * @covers \Drupal\project_browser\ProjectBrowser\Filter\MultipleChoiceFilter
 */
class FiltersTest extends UnitTestCase {

  /**
   * Tests filters are serialized as expected.
   */
  public function testFiltersSerializeAsExpected(): void {
    $filter = new BooleanFilter(
      TRUE,
      new TranslatableMarkup('ON', string_translation: $this->getStringTranslationStub()),
      new TranslatableMarkup('OFF', string_translation: $this->getStringTranslationStub()),
      'I name the filter',
      'tribe',
    );
    $serialized = $filter->jsonSerialize();
    $this->assertSame('boolean', $serialized['_type']);
    $this->assertSame('ON', $serialized['on_label']);
    $this->assertSame('OFF', $serialized['off_label']);
    $this->assertSame('I name the filter', $serialized['name']);
    $this->assertSame('tribe', $serialized['group']);
    $this->assertTrue($serialized['value']);

    $filter = new MultipleChoiceFilter(
      ['a' => 'Choice A', 'b' => 'Choice B'],
      ['a'],
      'I name thee multiple-choice',
      'cool_stuff',
    );
    $serialized = $filter->jsonSerialize();
    $this->assertSame('multiple_choice', $serialized['_type']);
    $this->assertSame(['a'], $serialized['value']);
    $this->assertSame(['a' => 'Choice A', 'b' => 'Choice B'], $serialized['choices']);
    $this->assertSame('I name thee multiple-choice', $serialized['name']);
    $this->assertSame('cool_stuff', $serialized['group']);
  }

}
