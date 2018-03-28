<?php

namespace Drupal\Tests\islandora\Functional;

use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\Tests\media_entity\Functional\MediaEntityFunctionalTestTrait;

/**
 * Tests the RelatedLinkHeader view alter.
 *
 * @group islandora
 */
class NodeLinkHeaderTest extends IslandoraFunctionalTestBase {

  use EntityReferenceTestTrait;
  use MediaEntityFunctionalTestTrait;

  /**
   * Node that has entity reference field.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $referencer;

  /**
   * Node that has entity reference field, but it's empty.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $referenced;

  /**
   * Media to be referenced (to check authZ).
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $media;

  /**
   * Node of a bundle that does _not_ have an entity reference field.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $other;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->other = $this->container->get('entity_type.manager')->getStorage('node')->create([
      'type' => 'test_type',
      'title' => 'Test object w/o entity reference field',
    ]);
    $this->other->save();

    $this->referenced = $this->container->get('entity_type.manager')->getStorage('node')->create([
      'type' => 'test_type_with_reference',
      'title' => 'Referenced',
    ]);
    $this->referenced->save();

    $media_bundle = $this->drupalCreateMediaBundle();
    $this->media = $this->container->get('entity_type.manager')->getStorage('media')->create([
      'bundle' => $media_bundle->id(),
      'name' => 'Media',
    ]);
    $this->media->save();

    $this->referencer = $this->container->get('entity_type.manager')->getStorage('node')->create([
      'type' => 'test_type_with_reference',
      'title' => 'Referencer',
      'field_node' => [$this->referenced->id()],
      'field_media' => [$this->media->id()],
    ]);
    $this->referencer->save();
  }

  /**
   * @covers \Drupal\islandora\EventSubscriber\NodeLinkHeaderSubscriber::onResponse
   */
  public function testNodeLinkHeaders() {
    // Create a test user that can see media.
    $account = $this->drupalCreateUser([
      'view media',
    ]);
    $this->drupalLogin($account);

    // Visit the other, there should not be a related header since it does not
    // even have the field.
    $this->drupalGet('node/' . $this->other->id());
    $this->assertTrue(
      $this->doesNotHaveLinkHeader('related'),
      "Node that does not have entity reference field must not return related link header."
    );

    // Visit the referenced node, there should not be a related header since
    // its entity reference field is empty.
    $this->drupalGet('node/' . $this->referenced->id());
    $this->assertTrue(
      $this->doesNotHaveLinkHeader('related'),
      "Node that has empty entity reference field must not return link header."
    );

    // Visit the referencer. It should return a rel="related" link header
    // for both the referenced node and media entity.
    $this->drupalGet('node/' . $this->referencer->id());
    $this->assertTrue(
      $this->validateLinkHeaderWithEntity('related', $this->referenced, 'Referenced Node') == 1,
      "Malformed related link header"
    );
    $this->assertTrue(
      $this->validateLinkHeaderWithEntity('related', $this->media, 'Referenced Media') == 1,
      "Malformed related link header"
    );

    // Check for links to REST endpoints for metadata.
    $entity_url = $this->referencer->toUrl('canonical', ['absolute' => TRUE])
      ->toString();
    $this->assertTrue(
      $this->validateLinkHeaderWithUrl('alternate', "$entity_url?_format=json", NULL, 'application/json') == 1,
      "Node must have link header pointing to json REST endpoint."
    );
    $this->assertTrue(
      $this->validateLinkHeaderWithUrl('alternate', "$entity_url?_format=jsonld", NULL, 'application/ld+json') == 1,
      "Node must have link header pointing to jsonld REST endpoint."
    );
    $this->assertTrue(
      $this->validateLinkHeaderWithUrl('alternate', "$entity_url?_format=xml", NULL, 'application/xml') == 0,
      "Node must not have link header pointing to disabled xml REST endpoint."
    );

    // Check that the current representation is not advertised when visitng
    // a REST endpoint (e.g. the json link header doesn't appear when you're
    // visiting the ?_format=json endpoint).
    $this->drupalGet('node/' . $this->referencer->id(), ['query' => ['_format' => 'json']]);
    $this->assertTrue(
      $this->validateLinkHeaderWithUrl('alternate', "$entity_url?_format=json", NULL, 'application/json') == 0,
      "Node must not have link header pointing to json REST endpoint when vising the json REST endpoint."
    );
    $this->assertTrue(
      $this->validateLinkHeaderWithUrl('alternate', "$entity_url?_format=jsonld", NULL, 'application/ld+json') == 1,
      "Node must have link header pointing to jsonld REST endpoint when visiting the json REST endpoint."
    );

    // Log in as anonymous.
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);

    // Visit the referencer. It should return a rel="related" link header
    // for both the referenced node bun not the media entity b/c permissions.
    $this->drupalGet('node/' . $this->referencer->id());
    $this->assertTrue(
      $this->validateLinkHeaderWithEntity('related', $this->referenced, 'Referenced Node') == 1,
      "Malformed related link header"
    );
    $this->assertTrue(
      $this->validateLinkHeaderWithEntity('related', $this->media, 'Referenced Media') == 0,
      "Anonymous should not be able to see media link header"
    );
  }

}
