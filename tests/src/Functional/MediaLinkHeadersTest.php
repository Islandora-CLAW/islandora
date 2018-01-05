<?php

namespace Drupal\Tests\islandora\Functional;

/**
 * Tests the RelatedLinkHeader view alter.
 *
 * @group islandora
 */
class MediaLinkHeadersTest extends IslandoraFunctionalTestBase {

  /**
   * @covers \Drupal\islandora\ViewAlter\MediaLinkHeaders::alter
   */
  public function testMediaLinkHeaders() {
    // Create a test user.
    $account = $this->drupalCreateUser([
      'view media',
      'create media',
      'update media',
    ]);
    $this->drupalLogin($account);

    $urls = $this->createThumbnailWithFile();
    $this->drupalGet($urls['media']);
    $this->assertTrue(
      $this->validateLinkHeader('describes', $urls['file']['file'], '', 'image/png') == 1,
      "Malformed 'describes' link header"
    );
    $this->assertTrue(
      $this->validateLinkHeader('edit-media', $urls['file']['rest'], '', 'application/json') == 1,
      "Malformed 'edit-media' link header"
    );
  }

}
