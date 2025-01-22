<?php

declare(strict_types=1);

namespace Drupal\project_browser\ProjectBrowser\Filter;

/**
 * A base class for all filters that can be defined by source plugins.
 */
abstract class FilterBase implements \JsonSerializable {

  public function __construct(
    public readonly string|\Stringable $name,
    public readonly string|\Stringable|null $group,
  ) {}

  /**
   * {@inheritdoc}
   */
  final public function jsonSerialize(): array {
    $values = [
      '_type' => match (static::class) {
        BooleanFilter::class => 'boolean',
        MultipleChoiceFilter::class => 'multiple_choice',
      },
    ] + get_object_vars($this);

    return array_map(
      fn ($value) => $value instanceof \Stringable ? (string) $value : $value,
      $values,
    );
  }

}
