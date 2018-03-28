<?php

namespace Drupal\Tests\islandora\Functional;

/**
 * Tests indexing and de-indexing in hooks with pre-configured actions.
 *
 * @group islandora
 */
class DerivativeReactionTest extends IslandoraFunctionalTestBase {

  /**
   * @covers \Drupal\islandora\DerivativeUtils::executeDerivativeReactions
   */
  public function testExecuteDerivativeReaction() {
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

    $this->createContext('Test', 'test');
    $this->addPresetReaction('test', 'derivative', 'hello_world');

    // Create a new media.
    $urls = $this->createThumbnailWithFile();

    // Media is not referenced, so derivatives should not fire.
    $this->assertSession()->pageTextNotContains("Hello World!");

    // Create a new node without referencing a media and confirm derivatives
    // do not fire.
    $this->postNodeAddForm('test_type_with_reference', ['title[0][value]' => 'Test Node'], 'Save');
    $this->assertSession()->pageTextNotContains("Hello World!");

    // Create a new node that does reference media and confirm derivatives
    // do fire.
    $this->postNodeAddForm(
      'test_type_with_reference',
      [
        'title[0][value]' => 'Test Node 2',
        'field_media[0][target_id]' => 'Test Media',
      ],
      'Save'
    );
    $this->assertSession()->pageTextContains("Hello World!");

    // Stash the node's url.
    $url = $this->getUrl();

    // Edit the node but not the media and confirm derivatives do not fire.
    $this->postEntityEditForm($url, ['title[0][value]' => 'Test Node Changed'], 'Save');
    $this->assertSession()->pageTextNotContains("Hello World!");

    // Edit the Media now that it's referenced.
    $this->postEntityEditForm($urls['media'], ['field_image[0][alt]' => 'alt text changed'], 'Save');
    $this->assertSession()->pageTextContains("Hello World!");
  }

}
