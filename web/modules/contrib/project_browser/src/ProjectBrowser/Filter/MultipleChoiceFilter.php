<?php

declare(strict_types=1);

namespace Drupal\project_browser\ProjectBrowser\Filter;

/**
 * Defines a filter to choose any number of options from a list.
 */
final class MultipleChoiceFilter extends FilterBase {

  public function __construct(
    public readonly array $choices,
    public readonly array $value,
    mixed ...$arguments,
  ) {
    // Everything $value should be present in $choices.
    assert(array_diff($value, array_keys($choices)) === []);

    parent::__construct(...$arguments);
  }

}
