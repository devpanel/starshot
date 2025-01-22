<?php

declare(strict_types=1);

namespace Drupal\project_browser\ProjectBrowser\Filter;

/**
 * Defines a filter that can either be on, or off.
 */
final class BooleanFilter extends FilterBase {

  public function __construct(public bool $value, public string|\Stringable $on_label, public string|\Stringable $off_label, mixed ...$arguments) {
    parent::__construct(...$arguments);
  }

}
