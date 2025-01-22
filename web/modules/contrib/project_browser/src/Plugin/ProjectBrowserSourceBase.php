<?php

namespace Drupal\project_browser\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\project_browser\ProjectBrowser\Filter\MultipleChoiceFilter;
use Drupal\project_browser\ProjectBrowser\ProjectsResultsPage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an abstract base class for a Project Browser source.
 *
 * @see \Drupal\project_browser\Annotation\ProjectBrowserSource
 * @see \Drupal\project_browser\Plugin\ProjectBrowserSourceManager
 * @see plugin_api
 */
abstract class ProjectBrowserSourceBase extends PluginBase implements ProjectBrowserSourceInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFilterDefinitions(): array {
    $filters = [];

    $categories = $this->getCategories();
    if ($categories) {
      $choices = array_combine(
        array_column($categories, 'id'),
        array_column($categories, 'name'),
      );
      $filters['categories'] = new MultipleChoiceFilter($choices, [], $this->t('Categories'), $this->t('Categories'), NULL);
    }
    return $filters;
  }

  /**
   * Returns the available sort options that plugins will parse.
   *
   * @return array
   *   Options offered.
   */
  public function getSortOptions(): array {
    return [
      'usage_total' => [
        'id' => 'usage_total',
        'text' => $this->t('Most popular'),
      ],
      'a_z' => [
        'id' => 'a_z',
        'text' => $this->t('A-Z'),
      ],
      'z_a' => [
        'id' => 'z_a',
        'text' => $this->t('Z-A'),
      ],
      'created' => [
        'id' => 'created',
        'text' => $this->t('Newest first'),
      ],
      'best_match' => [
        'id' => 'best_match',
        'text' => $this->t('Most relevant'),
      ],
    ];
  }

  /**
   * Creates a page of results (projects) to send to the client side.
   *
   * @param \Drupal\project_browser\ProjectBrowser\Project[] $results
   *   The projects to list on the page.
   * @param int|null $total_results
   *   (optional) The total number of results. Defaults to the size of $results.
   * @param string|null $error
   *   (optional) Error message to be passed along, if any.
   *
   * @return \Drupal\project_browser\ProjectBrowser\ProjectsResultsPage
   *   A list of projects to send to the client.
   */
  protected function createResultsPage(array $results, ?int $total_results = NULL, ?string $error = NULL): ProjectsResultsPage {
    return new ProjectsResultsPage(
      $total_results ?? count($results),
      array_values($results),
      (string) $this->getPluginDefinition()['label'],
      $this->getPluginId(),
      $error
    );
  }

}
