<?php

namespace Drupal\Tests\islandora\Functional;

/**
 * Tests altering view modes with context ui.
 *
 * @group islandora
 */
class ViewModeAlterReactionTest extends IslandoraFunctionalTestBase {

  /**
   * @covers \Drupal\islandora\Plugin\ContextReaction\ViewModeAlterReaction::execute
   * @covers \Drupal\islandora\Plugin\ContextReaction\ViewModeAlterReaction::buildConfigurationForm
   * @covers \Drupal\islandora\Plugin\ContextReaction\ViewModeAlterReaction::submitConfigurationForm
   */
  public function testViewModeAlter() {

    // Create a test user.
    $account = $this->drupalCreateUser([
      'bypass node access',
      'administer contexts',
      'administer actions',
      'view media',
      'create media',
      'update media',
    ]);
    $this->drupalLogin($account);

    // Create a new media.
    $urls = $this->createThumbnailWithFile();

    // Create a new node referencing the media.
    $this->postNodeAddForm(
      'test_type_with_reference',
      [
        'title[0][value]' => 'Test Node',
        'field_media[0][target_id]' => 'Test Media',
      ],
      'Save'
    );

    // Stash the node's url.
    $url = $this->getUrl();

    // Make sure we're viewing the default (e.g. the media field is displayed).
    $this->assertSession()->pageTextContains("Referenced Media");

    // Create a context and set the view mode to alter to "teaser".
    $this->createContext('Test', 'test');

    $this->drupalGet("admin/structure/context/test/reaction/add/view_mode_alter");
    $this->getSession()->getPage()->findById("edit-reactions-view-mode-alter-mode")->selectOption('node.teaser');
    $this->getSession()->getPage()->pressButton(t('Save and continue'));
    $this->assertSession()->statusCodeEquals(200);

    drupal_flush_all_caches();

    // Re-visit the node and make sure we're in teaser mode (e.g. the media
    // field is not displayed).
    $this->drupalGet($url);
    $this->assertSession()->pageTextNotContains("Referenced Media");

  }

}
