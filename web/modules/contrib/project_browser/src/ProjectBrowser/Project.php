<?php

namespace Drupal\project_browser\ProjectBrowser;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;
use Drupal\project_browser\ActivationStatus;
use Drupal\project_browser\ProjectType;

/**
 * Defines a single Project.
 */
class Project implements \JsonSerializable {

  /**
   * A persistent ID for this project in non-volatile storage.
   *
   * This property is internal and should be ignored by source plugins.
   *
   * @var string
   *
   * @see \Drupal\project_browser\EnabledSourceHandler::getProjects()
   */
  public string $id;

  /**
   * The ID of the source plugin which exposed this project.
   *
   * This property is internal and should be ignored by source plugins.
   *
   * @var string
   *
   * @see \Drupal\project_browser\EnabledSourceHandler::getProjects()
   */
  public string $source;

  /**
   * The status of this project in the current site.
   *
   * This property is internal and should be ignored by source plugins.
   *
   * @var \Drupal\project_browser\ActivationStatus
   */
  public ActivationStatus $status;

  /**
   * The instructions, if any, to activate this project.
   *
   * This property is internal and should be ignored by source plugins.
   *
   * @var string|\Drupal\Core\Url|null
   *
   * @see \Drupal\project_browser\ActivatorInterface::getInstructions()
   */
  public string|Url|null $commands;

  /**
   * The project type (e.g., module, theme, recipe, or something else).
   *
   * @var \Drupal\project_browser\ProjectType|string
   */
  public readonly ProjectType|string $type;

  /**
   * Constructs a Project object.
   *
   * @param array $logo
   *   Logo of the project.
   * @param bool $isCompatible
   *   Whether the project is compatible with the current version of Drupal.
   * @param string $machineName
   *   Value of project_machine_name of the project.
   * @param array $body
   *   Body field of the project in array format.
   * @param string $title
   *   Title of the project.
   * @param array $author
   *   Author of the project in array format.
   * @param string $packageName
   *   The Composer package name of this project, e.g. `drupal/project_browser`.
   * @param int|null $projectUsageTotal
   *   (optional) Total number of sites known to be using this project, or NULL
   *   if this information is not known. Defaults to NULL.
   * @param bool|null $isCovered
   *   (optional) Whether or not the project is covered by security advisories,
   *   or NULL if this information is not known. Defaults to NULL.
   * @param bool|null $isMaintained
   *   (optional) Whether or not the project is considered maintained, or NULL
   *   if this information is not known. Defaults to NULL.
   * @param \Drupal\Core\Url|null $url
   *   URL of the project, if any. Defaults to NULL.
   * @param array $categories
   *   Value of module_categories of the project.
   * @param array $images
   *   Images of the project.
   * @param array $warnings
   *   Warnings for the project.
   * @param string|ProjectType $type
   *   The project type. Defaults to a module, but may be any string that is not
   *   one of the cases of \Drupal\project_browser\ProjectType.
   * @param string|null $id
   *   (optional) A local, source plugin-specific identifier for this project.
   *   Cannot contain slashes. Will be automatically generated if not passed.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $id contains slashes.
   */
  public function __construct(
    public array $logo,
    public bool $isCompatible,
    public string $machineName,
    private array $body,
    public string $title,
    public array $author,
    public string $packageName,
    public ?int $projectUsageTotal = NULL,
    public ?bool $isCovered = NULL,
    public ?bool $isMaintained = NULL,
    public ?Url $url = NULL,
    public array $categories = [],
    public array $images = [],
    public array $warnings = [],
    string|ProjectType $type = ProjectType::Module,
    ?string $id = NULL,
  ) {
    $this->setSummary($body);

    if (is_int($projectUsageTotal) && $projectUsageTotal < 0) {
      throw new \InvalidArgumentException('The $projectUsageTotal argument cannot be a negative number.');
    }

    if (is_string($type)) {
      // If the $type can't be mapped to a ProjectType case, use it as-is.
      $type = ProjectType::tryFrom($type) ?? $type;
    }
    $this->type = $type;

    // If no local ID was passed, generate it from the package name and machine
    // name, which are unlikely to change.
    if (empty($id)) {
      $id = str_replace('/', '-', [$packageName, $machineName]);
      $id = implode('-', $id);
      $id = trim($id, '-');
    }
    if (str_contains($id, '/')) {
      throw new \InvalidArgumentException("The project ID cannot contain slashes.");
    }
    $this->id = $id;
  }

  /**
   * Set the project short description.
   *
   * @param array $body
   *   Body in array format.
   *
   * @return $this
   */
  public function setSummary(array $body) {
    $this->body = $body;
    if (empty($this->body['summary'])) {
      $this->body['summary'] = $this->body['value'] ?? '';
    }
    $this->body['summary'] = Html::escape(strip_tags($this->body['summary']));
    $this->body['summary'] = Unicode::truncate($this->body['summary'], 200, TRUE, TRUE);
    return $this;
  }

  /**
   * Returns the selector id of the project.
   *
   * @return string
   *   Selector id of the project.
   */
  public function getSelectorId(): string {
    return str_replace('_', '-', $this->machineName);
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    $commands = $this->commands;
    if ($commands instanceof Url) {
      $commands = $commands->setAbsolute()->toString();
    }
    elseif (is_string($commands)) {
      $commands = Xss::filter($commands, [...Xss::getAdminTagList(), 'textarea', 'button']);
    }

    return [
      'is_compatible' => $this->isCompatible,
      'is_covered' => $this->isCovered,
      'project_usage_total' => $this->projectUsageTotal,
      'module_categories' => $this->categories,
      'project_machine_name' => $this->machineName,
      'project_images' => $this->images,
      'logo' => $this->logo,
      'body' => $this->body,
      'title' => $this->title,
      'author' => $this->author,
      'warnings' => $this->warnings,
      'package_name' => $this->packageName,
      'is_maintained' => $this->isMaintained,
      'url' => $this->url?->setAbsolute()->toString(),
      'status' => match ($this->status) {
        ActivationStatus::Absent => 'absent',
        ActivationStatus::Present => 'present',
        ActivationStatus::Active => 'active',
      },
      'selector_id' => $this->getSelectorId(),
      'commands' => $commands,
      'id' => $this->id,
      'source' => $this->source,
    ];
  }

}
