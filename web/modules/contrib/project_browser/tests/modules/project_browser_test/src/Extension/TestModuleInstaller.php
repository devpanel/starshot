<?php

namespace Drupal\project_browser_test\Extension;

use Drupal\Core\Extension\ModuleInstaller;

/**
 * Conditional Module installer for test.
 *
 * @see \Drupal\Core\Extension\ModuleInstaller::install
 */
class TestModuleInstaller extends ModuleInstaller {

  /**
   * Take over install if module name is cream_cheese or kangaroo.
   *
   * @param array $module_list
   *   An array of module machine names.
   * @param bool $enable_dependencies
   *   True if dependencies should be enabled.
   */
  public function install(array $module_list, $enable_dependencies = TRUE) {
    if (!empty(array_intersect(['cream_cheese', 'kangaroo'], $module_list))) {
      return TRUE;
    }
    return parent::install($module_list, $enable_dependencies);
  }

}
