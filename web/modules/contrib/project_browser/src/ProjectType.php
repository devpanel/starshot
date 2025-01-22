<?php

declare(strict_types=1);

namespace Drupal\project_browser;

/**
 * The different project types known to Project Browser.
 *
 * @see \Drupal\project_browser\ProjectBrowser\Project
 */
enum ProjectType: string {

  case Module = 'module';
  case Recipe = 'recipe';

}
