<?php

declare(strict_types=1);

namespace Drupal\trash\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the value is a valid auto-purge period when calling strtotime().
 *
 * @Constraint(
 *   id = "ValidAutoPurgePeriod",
 *   label = @Translation("Auto-purge period", context = "Validation")
 * )
 */
class ValidAutoPurgePeriodConstraint extends Constraint {

  /**
   * The error message.
   *
   * @var string
   */
  public string $message = "The time period '@value' is not valid. Some valid values would be '1 month, 10 days', '15 days', '3 hours, 15 minutes'.";

}
