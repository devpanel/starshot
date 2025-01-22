<?php

declare(strict_types=1);

namespace Drupal\project_browser;

/**
 * Defines the possible states of a project in the current site.
 */
enum ActivationStatus {

  // Not physically present, but can be required and activated.
  case Absent;
  // Physically present, but not yet activated.
  case Present;
  // Physically present and activated.
  case Active;

}
