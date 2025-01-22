<?php

namespace Drupal\Tests\trash\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the basic trash functionality on nodes.
 *
 * @group trash
 */
class TrashNodeTest extends BrowserTestBase {

  /**
   * A user with permission to trash content but not restoring.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * A user with permission to trash, restore and purge the trash bin.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'node', 'trash'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create Basic page node type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType([
        'type' => 'page',
        'name' => 'Basic Page',
        'display_submitted' => FALSE,
      ]);
    }

    $this->webUser = $this->drupalCreateUser([
      'access content',
      'create page content',
      'edit own page content',
      'delete own page content',
    ]);
    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'create page content',
      'edit own page content',
      'delete own page content',
      'edit any page content',
      'delete any page content',
      'administer nodes',
      'bypass node access',
      'access trash',
      'view deleted entities',
      'purge node entities',
      'restore node entities',
    ]);
    $this->drupalPlaceBlock('local_tasks_block', ['id' => 'page_tabs_block']);
    $this->drupalPlaceBlock('local_actions_block', ['id' => 'page_actions_block']);
  }

  /**
   * Test moving a node to the trash bin and restoring it.
   */
  public function testTrashAndRestoreNode() {
    // Login as a regular user.
    $this->drupalLogin($this->webUser);

    // Create "Basic page" content with title.
    $settings = [
      'title' => $this->randomMachineName(8),
    ];
    $node = $this->drupalCreateNode($settings);

    // Load the node edit form.
    $this->drupalGet('node/' . $node->id() . '/edit');

    // Make sure the task is there.
    $this->assertSession()->linkExists('Delete');

    // Now edit the same node as an admin user.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/' . $node->id() . '/edit');

    // Make sure we can move to the trash bin.
    $this->assertSession()->linkExists('Delete');

    // Make sure the link works as expected.
    $this->clickLink('Delete');
    $this->assertSession()->addressEquals('node/1/delete');

    $this->assertSession()->pageTextContains('Deleting this content item will move it to the trash. You can restore it from the trash at a later date if necessary.');
    $this->submitForm([], 'Delete');

    // The content has been moved to the trash.
    $this->assertSession()->statusMessageContains('The Basic Page ' . $node->getTitle() . ' has been deleted', 'status');
    // I can see it in the trash context.
    $this->drupalGet('node/1', ['query' => ['in_trash' => 1]]);

    $this->assertSession()->elementExists('css', 'article.is-deleted');

    // I can't see the node anymore with a regular editor.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->statusCodeEquals(404);

    // I can restore the content.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/content/trash');
    $this->assertSession()->linkExists('Restore');
    $this->clickLink('Restore');
    $this->submitForm([], 'Confirm');
    $this->assertSession()->statusMessageContains('The content item ' . $node->getTitle() . ' has been restored from trash.', 'status');
  }

  /**
   * Test moving a node to the trash bin and purging it.
   */
  public function testPurgingNode() {
    // Login as a privileged user.
    $this->drupalLogin($this->adminUser);

    // Create "Basic page" content with title.
    $settings = [
      'title' => $this->randomMachineName(8),
    ];
    $node = $this->drupalCreateNode($settings);

    // Load the node edit form.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->clickLink('Delete');
    $this->submitForm([], 'Delete');

    // The content has been moved to the trash.
    $this->assertSession()->statusMessageContains('The Basic Page ' . $node->getTitle() . ' has been deleted', 'status');

    // Make sure we can Purge.
    $this->drupalGet('/admin/content/trash');
    $this->assertSession()->linkExists('Purge');
    $this->clickLink('Purge');
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->submitForm([], 'Confirm');
    $this->assertSession()->statusMessageContains('The content item ' . $node->getTitle() . ' has been permanently deleted.', 'status');
  }

}
