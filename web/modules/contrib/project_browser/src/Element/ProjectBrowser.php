<?php

namespace Drupal\project_browser\Element;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\ElementInterface;
use Drupal\Core\Render\Element\RenderElementBase;
use Drupal\project_browser\EnabledSourceHandler;
use Drupal\project_browser\InstallReadiness;
use Drupal\project_browser\Plugin\ProjectBrowserSourceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a render element for the Project Browser.
 *
 * @RenderElement("project_browser")
 */
#[RenderElement('project_browser')]
final class ProjectBrowser implements ElementInterface, ContainerFactoryPluginInterface {

  use DependencySerializationTrait;

  public function __construct(
    private readonly string $pluginId,
    private readonly mixed $pluginDefinition,
    private readonly EnabledSourceHandler $enabledSourceHandler,
    private readonly ?InstallReadiness $installReadiness,
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getPluginId(): string {
    return $this->pluginId;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition(): mixed {
    return $this->pluginDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $plugin_id,
      $plugin_definition,
      $container->get(EnabledSourceHandler::class),
      $container->get(InstallReadiness::class, ContainerInterface::NULL_ON_INVALID_REFERENCE),
      $container->get(ModuleHandlerInterface::class),
      $container->get(ConfigFactoryInterface::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      '#theme' => 'project_browser_main_app',
      '#attached' => [
        'library' => [
          'project_browser/svelte',
        ],
        'drupalSettings' => [
          'project_browser' => [],
        ],
      ],
      '#pre_render' => [
        [$this, 'attachProjectBrowserSettings'],
      ],
    ];
  }

  /**
   * Prepares a render element for Project Browser.
   *
   * @param array $element
   *   A render element array.
   *
   * @return array
   *   The render element array.
   */
  public function attachProjectBrowserSettings(array $element): array {
    $element['#attached']['drupalSettings']['project_browser'] = $this->getDrupalSettings(
      $element['#source'],
      $element['#max_selections'] ?? $this->configFactory->get('project_browser.admin_settings')->get('max_selections') ?? NULL,
    );
    return $element;
  }

  /**
   * Gets the Drupal settings for the Project Browser.
   *
   * @param string $source
   *   The ID of the source plugin to query for projects.
   * @param int|null $max_selections
   *   (optional) The maximum number of project to install at once, or NULL for
   *   no limit. Defaults to NULL.
   *
   * @return array
   *   An array of Drupal settings.
   */
  private function getDrupalSettings(string $source, ?int $max_selections = NULL): array {
    $source = $this->enabledSourceHandler->getCurrentSources()[$source];
    assert($source instanceof ProjectBrowserSourceInterface);

    if (is_int($max_selections) && $max_selections <= 0) {
      throw new \InvalidArgumentException('$max_selections must be a positive integer or NULL.');
    }

    $package_manager = [
      'available' => (bool) $this->configFactory->get('project_browser.admin_settings')->get('allow_ui_install'),
      'errors' => [],
      'warnings' => [],
      'status_checked' => FALSE,
    ];
    // @todo Fix https://www.drupal.org/node/3497624 to avoid adding
    // hard-coded values. #techdebt
    if ($source !== 'recipes' && $package_manager['available']) {
      $package_manager = array_merge($package_manager, $this->installReadiness->validatePackageManager());
      $package_manager['status_checked'] = TRUE;
    }

    return [
      'active_plugin' => $source->getPluginDefinition()['label'],
      'module_path' => $this->moduleHandler->getModule('project_browser')->getPath(),
      'sort_options' => array_values($source->getSortOptions()),
      'default_plugin_id' => $source->getPluginId(),
      'package_manager' => $package_manager,
      'filters' => (object) $source->getFilterDefinitions(),
      'max_selections' => $max_selections,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function setAttributes(&$element, $class = []): void {
    DeprecationHelper::backwardsCompatibleCall(
      \Drupal::VERSION,
      '10.3',
      static fn () => RenderElementBase::setAttributes($element, $class),
      static fn () => Element::setAttributes($element, $class)
    );
  }

}
