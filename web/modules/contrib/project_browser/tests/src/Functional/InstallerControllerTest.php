<?php

namespace Drupal\Tests\project_browser\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\Tests\ApiRequestTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\project_browser\Traits\PackageManagerFixtureUtilityTrait;
use Drupal\package_manager\Event\PostApplyEvent;
use Drupal\package_manager\Event\PostCreateEvent;
use Drupal\package_manager\Event\PostRequireEvent;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Event\PreRequireEvent;
use Drupal\package_manager\ValidationResult;
use Drupal\package_manager_test_validation\EventSubscriber\TestSubscriber;
use Drupal\project_browser\ComposerInstaller\Installer;
use Drupal\project_browser\EnabledSourceHandler;
use Drupal\project_browser\InstallState;
use Drupal\project_browser_test\Datetime\TestTime;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

// cspell:ignore crashmore

/**
 * Tests the installer controller.
 *
 * @coversDefaultClass \Drupal\project_browser\Controller\InstallerController
 *
 * @group project_browser
 */
class InstallerControllerTest extends BrowserTestBase {

  use PackageManagerFixtureUtilityTrait;
  use ApiRequestTrait;

  /**
   * A stage id.
   *
   * @var string
   */
  protected $stageId;

  /**
   * The installer.
   *
   * @var \Drupal\project_browser\ComposerInstaller\Installer
   */
  private $installer;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'package_manager_bypass',
    'package_manager',
    'package_manager_test_validation',
    'project_browser',
    'project_browser_test',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Setup for install controller test.
   */
  protected function setUp(): void {
    parent::setUp();
    $connection = $this->container->get('database');
    $query = $connection->insert('project_browser_projects')->fields([
      'nid',
      'title',
      'author',
      'created',
      'project_usage_total',
      'maintenance_status',
      'development_status',
      'status',
      'field_security_advisory_coverage',
      'field_project_type',
      'project_data',
      'field_project_machine_name',
    ]);
    $query->values([
      'nid' => 111,
      'title' => 'An Awesome Module',
      'author' => 'Detective Crashmore',
      'created' => 1383917647,
      'project_usage_total' => 455,
      'maintenance_status' => 13028,
      'development_status' => 9988,
      'status' => 1,
      'field_security_advisory_coverage' => 'covered',
      'field_project_type' => 'full',
      'project_data' => serialize([
        'body' => [
          'value' => $this->getRandomGenerator()->paragraphs(1),
        ],
      ]),
      'field_project_machine_name' => 'awesome_module',
    ]);
    $query->values([
      'nid' => 222,
      'title' => 'Security Revoked Module',
      'author' => 'Jamie Taco',
      'created' => 1383917448,
      'project_usage_total' => 455,
      'maintenance_status' => 13028,
      'development_status' => 9988,
      'status' => 1,
      'field_security_advisory_coverage' => 'covered',
      'field_project_type' => 'full',
      'project_data' => serialize([
        'body' => [
          'value' => $this->getRandomGenerator()->paragraphs(1),
        ],
      ]),
      'field_project_machine_name' => 'security_revoked_module',
    ]);
    $query->values([
      'nid' => 333,
      'title' => 'Drupal core',
      'author' => 'The usual gang of geniuses',
      'created' => 1383917647,
      'project_usage_total' => 987654321,
      'maintenance_status' => 13028,
      'development_status' => 9988,
      'status' => 1,
      'field_security_advisory_coverage' => 'covered',
      'field_project_type' => 'full',
      'project_data' => serialize([
        'body' => [
          'value' => $this->getRandomGenerator()->paragraphs(1),
        ],
      ]),
      'field_project_machine_name' => 'core',
    ]);
    $query->values([
      'nid' => 444,
      'title' => 'Metatag',
      'author' => 'Dr. Doom',
      'created' => 1383917448,
      'project_usage_total' => 455,
      'maintenance_status' => 13028,
      'development_status' => 9988,
      'status' => 1,
      'field_security_advisory_coverage' => 'covered',
      'field_project_type' => 'full',
      'project_data' => serialize([
        'body' => [
          'value' => $this->getRandomGenerator()->paragraphs(1),
        ],
      ]),
      'field_project_machine_name' => 'metatag',
    ]);
    $query->execute();
    $this->initPackageManager();
    $this->installer = $this->container->get(Installer::class);
    $this->drupalLogin($this->drupalCreateUser(['administer modules']));
    $this->config('project_browser.admin_settings')
      ->set('enabled_sources', ['project_browser_test_mock', 'drupal_core'])
      ->set('allow_ui_install', TRUE)
      ->save();

    // Prime the non-volatile cache.
    $this->container->get(EnabledSourceHandler::class)->getProjects('project_browser_test_mock');
  }

  /**
   * Confirms install endpoint not available if UI installs are not enabled.
   *
   * @covers ::access
   */
  public function testUiInstallUnavailableIfDisabled() {
    $this->config('project_browser.admin_settings')->set('allow_ui_install', FALSE)->save();
    $this->drupalGet('admin/modules/project_browser/install-begin');
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->pageTextContains('Access denied');
  }

  /**
   * Confirms prevention of requiring modules with revoked security status.
   *
   * @covers ::begin
   */
  public function testInstallSecurityRevokedModule() {
    $this->assertSame([], $this->container->get(InstallState::class)->toArray());
    $contents = $this->drupalGet('admin/modules/project_browser/install-begin');
    $this->stageId = Json::decode($contents)['stage_id'];
    $response = $this->getPostResponse('project_browser.stage.require', 'project_browser_test_mock/security_revoked_module', [
      'stage_id' => $this->stageId,
    ]);
    $this->assertSame(500, (int) $response->getStatusCode());
    $this->assertSame('{"message":"security_revoked_module is not safe to add because its security coverage has been revoked"}', (string) $response->getBody());
  }

  /**
   * Confirms a require will stop if package already present.
   *
   * @covers ::require
   */
  public function testInstallAlreadyPresentPackage() {
    $this->assertSame([], $this->container->get(InstallState::class)->toArray());
    // Though core is not available as a choice in project browser, it works
    // well for the purposes of this test as it's definitely already added
    // via composer.
    $contents = $this->drupalGet('admin/modules/project_browser/install-begin');
    $this->stageId = Json::decode($contents)['stage_id'];
    $response = $this->getPostResponse('project_browser.stage.require', 'project_browser_test_mock/core', [
      'stage_id' => $this->stageId,
    ]);
    $this->assertSame(500, (int) $response->getStatusCode());
    $this->assertSame('{"message":"StageEventException: The following package is already installed: drupal\/core\n","phase":"require"}', (string) $response->getBody());
  }

  /**
   * Calls the endpoint that begins installation.
   *
   * @covers ::begin
   */
  private function doStart() {
    $this->assertSame([], $this->container->get(InstallState::class)->toArray());
    $contents = $this->drupalGet('admin/modules/project_browser/install-begin');
    $this->stageId = Json::decode($contents)['stage_id'];
    $this->assertSession()->statusCodeEquals(200);
    $expected_output = sprintf('{"phase":"create","status":0,"stage_id":"%s"}', $this->stageId);
  }

  /**
   * Calls the endpoint that continues to the require phase of installation.
   *
   * @covers ::require
   */
  private function doRequire() {
    $response = $this->getPostResponse('project_browser.stage.require', 'project_browser_test_mock/awesome_module', [
      'stage_id' => $this->stageId,
    ]);
    $expected_output = sprintf('{"phase":"create","status":0,"stage_id":"%s"}', $this->stageId);
    $this->assertSame($expected_output, $this->getSession()->getPage()->getContent());
    $this->assertInstallInProgress('project_browser_test_mock/awesome_module', 'project_browser_test_mock', 'requiring');
  }

  /**
   * Calls the endpoint that continues to the apply phase of installation.
   *
   * @covers ::apply
   */
  private function doApply() {
    $this->drupalGet("/admin/modules/project_browser/install-apply/$this->stageId");
    $expected_output = sprintf('{"phase":"apply","status":0,"stage_id":"%s"}', $this->stageId);
    $this->assertSame($expected_output, $this->getSession()->getPage()->getContent());
    $this->assertInstallInProgress('project_browser_test_mock/awesome_module', 'project_browser_test_mock', 'applying');
  }

  /**
   * Calls the endpoint that continues to the post apply phase of installation.
   *
   * @covers ::postApply
   */
  private function doPostApply() {
    $this->drupalGet("/admin/modules/project_browser/install-post_apply/$this->stageId");
    $expected_output = sprintf('{"phase":"post apply","status":0,"stage_id":"%s"}', $this->stageId);
    $this->assertSame($expected_output, $this->getSession()->getPage()->getContent());
    $this->assertInstallInProgress('project_browser_test_mock/awesome_module', 'project_browser_test_mock', 'applying');
  }

  /**
   * Calls the endpoint that continues to the destroy phase of installation.
   *
   * @covers ::destroy
   */
  private function doDestroy() {
    $this->drupalGet("/admin/modules/project_browser/install-destroy/$this->stageId");
    $expected_output = sprintf('{"phase":"destroy","status":0,"stage_id":"%s"}', $this->stageId);
    $this->assertSame($expected_output, $this->getSession()->getPage()->getContent());
    $this->assertInstallInProgress('project_browser_test_mock/awesome_module', 'project_browser_test_mock', 'applying');
  }

  /**
   * Calls every endpoint needed to do a UI install and confirms they work.
   */
  public function testUiInstallerEndpoints() {
    $this->doStart();
    $this->doRequire();
    $this->doApply();
    $this->doPostApply();
    $this->doDestroy();
  }

  /**
   * Tests an error during a pre create event.
   *
   * @covers ::create
   */
  public function testPreCreateError() {
    $message = t('This is a PreCreate error.');
    $result = ValidationResult::createError([$message]);
    TestSubscriber::setTestResult([$result], PreCreateEvent::class);
    $contents = $this->drupalGet('admin/modules/project_browser/install-begin');
    $this->assertSession()->statusCodeEquals(500);
    $this->assertSame('{"message":"StageEventException: This is a PreCreate error.\n","phase":"create"}', $contents);
  }

  /**
   * Tests an exception during a pre create event.
   *
   * @covers ::create
   */
  public function testPreCreateException() {
    $error = new \Exception('PreCreate did not go well.');
    TestSubscriber::setException($error, PreCreateEvent::class);
    $contents = $this->drupalGet('admin/modules/project_browser/install-begin');
    $this->assertSession()->statusCodeEquals(500);
    $this->assertSame('{"message":"StageEventException: PreCreate did not go well.","phase":"create"}', $contents);
  }

  /**
   * Tests an exception during a post create event.
   *
   * @covers ::create
   */
  public function testPostCreateException() {
    $error = new \Exception('PostCreate did not go well.');
    TestSubscriber::setException($error, PostCreateEvent::class);
    $contents = $this->drupalGet('admin/modules/project_browser/install-begin');
    $this->assertSession()->statusCodeEquals(500);
    $this->assertSame('{"message":"StageEventException: PostCreate did not go well.","phase":"create"}', $contents);
  }

  /**
   * Tests an error during a pre require event.
   *
   * @covers ::require
   */
  public function testPreRequireError() {
    $message = t('This is a PreRequire error.');
    $result = ValidationResult::createError([$message]);
    $this->doStart();
    TestSubscriber::setTestResult([$result], PreRequireEvent::class);
    $response = $this->getPostResponse('project_browser.stage.require', 'project_browser_test_mock/awesome_module', [
      'stage_id' => $this->stageId,
    ]);
    $this->assertSame(500, (int) $response->getStatusCode());
    $this->assertSame('{"message":"StageEventException: This is a PreRequire error.\n","phase":"require"}', (string) $response->getBody());
  }

  /**
   * Tests an exception during a pre require event.
   *
   * @covers ::require
   */
  public function testPreRequireException() {
    $error = new \Exception('PreRequire did not go well.');
    TestSubscriber::setException($error, PreRequireEvent::class);
    $this->doStart();
    $response = $this->getPostResponse('project_browser.stage.require', 'project_browser_test_mock/awesome_module', [
      'stage_id' => $this->stageId,
    ]);
    $this->assertSame(500, (int) $response->getStatusCode());
    $this->assertSame('{"message":"StageEventException: PreRequire did not go well.","phase":"require"}', (string) $response->getBody());
  }

  /**
   * Tests an exception during a post require event.
   *
   * @covers ::require
   */
  public function testPostRequireException() {
    $error = new \Exception('PostRequire did not go well.');
    TestSubscriber::setException($error, PostRequireEvent::class);
    $this->doStart();
    $response = $this->getPostResponse('project_browser.stage.require', 'project_browser_test_mock/awesome_module', [
      'stage_id' => $this->stageId,
    ]);
    $this->assertSame(500, (int) $response->getStatusCode());
    $this->assertSame('{"message":"StageEventException: PostRequire did not go well.","phase":"require"}', (string) $response->getBody());
  }

  /**
   * Tests an error during a pre apply event.
   *
   * @covers ::apply
   */
  public function testPreApplyError() {
    $message = t('This is a PreApply error.');
    $result = ValidationResult::createError([$message]);
    TestSubscriber::setTestResult([$result], PreApplyEvent::class);
    $this->doStart();
    $this->doRequire();
    $contents = $this->drupalGet("/admin/modules/project_browser/install-apply/$this->stageId");
    $this->assertSession()->statusCodeEquals(500);
    $this->assertSame('{"message":"StageEventException: This is a PreApply error.\n","phase":"apply"}', $contents);
  }

  /**
   * Tests an exception during a pre apply event.
   *
   * @covers ::apply
   */
  public function testPreApplyException() {
    $error = new \Exception('PreApply did not go well.');
    TestSubscriber::setException($error, PreApplyEvent::class);
    $this->doStart();
    $this->doRequire();
    $contents = $this->drupalGet("/admin/modules/project_browser/install-apply/$this->stageId");
    $this->assertSession()->statusCodeEquals(500);
    $this->assertSame('{"message":"StageEventException: PreApply did not go well.","phase":"apply"}', $contents);
  }

  /**
   * Tests an exception during a post apply event.
   *
   * @covers ::apply
   */
  public function testPostApplyException() {
    $error = new \Exception('PostApply did not go well.');
    TestSubscriber::setException($error, PostApplyEvent::class);
    $this->doStart();
    $this->doRequire();
    $this->doApply();
    $contents = $this->drupalGet("/admin/modules/project_browser/install-post_apply/$this->stageId");
    $this->assertSession()->statusCodeEquals(500);
    $this->assertSame('{"message":"StageEventException: PostApply did not go well.","phase":"post apply"}', $contents);
  }

  /**
   * Confirms the various versions of the "install in progress" messages.
   *
   * @covers ::unlock
   */
  public function testInstallUnlockMessage() {
    $this->doStart();
    $this->doRequire();

    $request_options = [
      'query' => ['source' => 'project_browser_test_mock'],
    ];

    $assert_unlock_response = function (string $response, string $expected_message): void {
      $response = Json::decode($response);
      $this->assertSame($expected_message, $response['message']);

      if ($response['unlock_url']) {
        $this->assertStringEndsWith('/admin/modules/project_browser/install/unlock', parse_url($response['unlock_url'], PHP_URL_PATH));
        $query = parse_url($response['unlock_url'], PHP_URL_QUERY);
        parse_str($query, $query);
        $this->assertNotEmpty($query['token']);
        $this->assertStringEndsWith('/admin/modules/browse/project_browser_test_mock', $query['destination']);
      }
    };

    // Check for mid install unlock offer message.
    $response = $this->drupalGet('admin/modules/project_browser/install-begin', $request_options);
    $this->assertSession()->statusCodeEquals(418);
    $assert_unlock_response($response, "The process for adding the project that was locked less than 1 minutes ago might still be in progress. Consider waiting a few more minutes before using [+unlock link].");
    $expected = [
      'project_browser_test_mock/awesome_module' => [
        'source' => 'project_browser_test_mock',
        'status' => 'requiring',
      ],
    ];
    $this->assertSame($expected, $this->container->get(InstallState::class)->toArray());
    $this->assertFalse($this->installer->isAvailable());
    $this->assertFalse($this->installer->isApplying());
    TestTime::setFakeTimeByOffset("+800 seconds");
    $response = $this->drupalGet('admin/modules/project_browser/install-begin', $request_options);
    $this->assertSession()->statusCodeEquals(418);
    $this->assertFalse($this->installer->isAvailable());
    $this->assertFalse($this->installer->isApplying());
    $assert_unlock_response($response, "The process for adding the project was locked 13 minutes ago. Use [+ unlock link] to unlock the process.");
    $this->doApply();
    TestTime::setFakeTimeByOffset('+800 seconds');
    $response = $this->drupalGet('admin/modules/project_browser/install-begin', $request_options);
    $this->assertSession()->statusCodeEquals(418);
    $this->assertFalse($this->installer->isAvailable());
    $this->assertTrue($this->installer->isApplying());
    $assert_unlock_response($response, "The process for adding the project was locked 13 minutes ago. It should not be unlocked while changes are being applied to the site.");
    TestTime::setFakeTimeByOffset("+55 minutes");
    $response = $this->drupalGet('admin/modules/project_browser/install-begin', $request_options);
    $this->assertSession()->statusCodeEquals(418);
    $assert_unlock_response($response, "The process for adding the project was locked 55 minutes ago. It should not be unlocked while changes are being applied to the site.");
    // Unlocking the stage becomes possible after 1 hour regardless of source.
    TestTime::setFakeTimeByOffset("+75 minutes");
    $response = $this->drupalGet('admin/modules/project_browser/install-begin', $request_options);
    $this->assertSession()->statusCodeEquals(418);
    $assert_unlock_response($response, "The process for adding the project was locked 1 hours, 15 minutes ago. Use [+ unlock link] to unlock the process.");
  }

  /**
   * Confirms the break lock link is available and works.
   *
   * The break lock link is not available once the stage is applying.
   *
   * @covers ::unlock
   */
  public function testCanBreakLock() {
    $this->doStart();
    // Try beginning another install while one is in progress, but not yet in
    // the applying stage.
    $content = $this->drupalGet('admin/modules/project_browser/install-begin', [
      'query' => [
        'source' => 'project_browser_test_mock',
      ],
    ]);
    $this->assertSession()->statusCodeEquals(418);
    $this->assertFalse($this->installer->isAvailable());
    $this->assertFalse($this->installer->isApplying());
    $json = Json::decode($content);
    $this->assertSame('The process for adding projects is locked, but that lock has expired. Use [+ unlock link] to unlock the process and try to add the project again.', $json['message']);
    $unlock_url = parse_url($json['unlock_url']);
    parse_str($unlock_url['query'], $unlock_url['query']);
    $unlock_content = $this->drupalGet($unlock_url['path'], ['query' => $unlock_url['query']]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($this->installer->isAvailable());
    $this->assertStringContainsString('Operation complete, you can add a new project again.', $unlock_content);
    $this->assertTrue($this->installer->isAvailable());
    $this->assertFalse($this->installer->isApplying());
  }

  /**
   * Confirms stage can be unlocked despite a missing Project Browser lock.
   *
   * @covers ::unlock
   */
  public function testCanBreakStageWithMissingProjectBrowserLock() {
    $this->doStart();
    $this->container->get(InstallState::class)->deleteAll();
    $content = $this->drupalGet('admin/modules/project_browser/install-begin', [
      'query' => ['source' => 'project_browser_test_mock'],
    ]);
    $this->assertSession()->statusCodeEquals(418);
    $this->assertFalse($this->installer->isAvailable());
    $this->assertFalse($this->installer->isApplying());
    $json = Json::decode($content);
    $this->assertSame('The process for adding projects is locked, but that lock has expired. Use [+ unlock link] to unlock the process and try to add the project again.', $json['message']);
    $unlock_url = parse_url($json['unlock_url']);
    parse_str($unlock_url['query'], $unlock_url['query']);
    $unlock_content = $this->drupalGet($unlock_url['path'], ['query' => $unlock_url['query']]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($this->installer->isAvailable());
    $this->assertStringContainsString('Operation complete, you can add a new project again.', $unlock_content);
    $this->assertTrue($this->installer->isAvailable());
    $this->assertFalse($this->installer->isApplying());
  }

  /**
   * Confirm a module and its dependencies can be installed via the endpoint.
   *
   * @covers ::activate
   */
  public function testCoreModuleActivate(): void {
    // Data for another source is cached in setUp, so we explicitly pass the
    // query parameter in getProjects() to ensure it uses the correct source.
    $this->container->get(EnabledSourceHandler::class)->getProjects('drupal_core', ['source' => 'drupal_core']);
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/modules');
    $assert_session->checkboxNotChecked('edit-modules-views-enable');
    $assert_session->checkboxNotChecked('edit-modules-views-ui-enable');

    $response = $this->getPostResponse('project_browser.activate', 'drupal_core/views_ui');
    $this->assertSame(200, (int) $response->getStatusCode());
    $this->assertSame('{"status":0}', (string) $response->getBody());

    $this->rebuildContainer();
    $this->drupalGet('admin/modules');
    $assert_session->checkboxChecked('edit-modules-views-enable');
    $assert_session->checkboxChecked('edit-modules-views-ui-enable');
  }

  /**
   * Confirms the project browser in progress input provides the expected value.
   *
   * @param string $project_id
   *   The ID of the project being enabled.
   * @param string $source
   *   The project source.
   * @param string $status
   *   The install state.
   */
  protected function assertInstallInProgress(string $project_id, string $source, ?string $status = NULL) {
    $expect_install[$project_id] = [
      'source' => $source,
      'status' => $status,
    ];
    $this->assertSame($expect_install, $this->container->get(InstallState::class)->toArray());
    $this->drupalGet("/admin/modules/project_browser/install_in_progress/$project_id");
    $this->assertSame(sprintf('{"status":1,"phase":"%s"}', $status), $this->getSession()->getPage()->getContent());
    $this->drupalGet('/admin/modules/project_browser/install_in_progress/project_browser_test_mock/metatag');
    $this->assertSame('{"status":0}', $this->getSession()->getPage()->getContent());
  }

  /**
   * Sends a POST request to the specified route with the provided project ID.
   *
   * @param string $route_name
   *   The route to which the POST request is sent.
   * @param string|string[] $project_id
   *   The project ID(s) to include in the POST request body.
   * @param array $route_parameters
   *   (optional) An associative array of route parameters, such as 'stage_id',
   *   that will be included in the URL.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response.
   */
  private function getPostResponse(string $route_name, string|array $project_id, array $route_parameters = []): ResponseInterface {
    $post_url = Url::fromRoute($route_name, $route_parameters);

    $request_options = [
      RequestOptions::HEADERS => [
        'Content-Type' => 'application/json',
      ],
    ];
    $request_options[RequestOptions::BODY] = Json::encode((array) $project_id);

    return $this->makeApiRequest('POST', $post_url, $request_options);
  }

}
