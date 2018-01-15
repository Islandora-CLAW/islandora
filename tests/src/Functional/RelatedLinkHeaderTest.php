<?php

namespace Drupal\Tests\islandora\Functional;

use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\Tests\media_entity\Functional\MediaEntityFunctionalTestTrait;

/**
 * Tests the RelatedLinkHeader view alter.
 *
 * @group islandora
 */
class RelatedLinkHeaderTest extends IslandoraFunctionalTestBase {

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

    // Create a test content type with an entity reference field.
    $test_type_with_reference = $this->container->get('entity_type.manager')->getStorage('node_type')->create([
      'type' => 'test_type_with_reference',
      'label' => 'Test Type With Reference',
    ]);
    $test_type_with_reference->save();

    // Add two entity reference fields.
    // One for nodes and one for media.
    $this->createEntityReferenceField('node', 'test_type_with_reference', 'field_reference', 'Referenced Entity', 'node', 'default', [], 2);
    $this->createEntityReferenceField('node', 'test_type_with_reference', 'field_media', 'Media Entity', 'media', 'default', [], 2);

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
      'field_reference' => [$this->referenced->id()],
      'field_media' => [$this->media->id()],
    ]);
    $this->referencer->save();
  }

  /**
   * @covers \Drupal\islandora\EventSubscriber\NodeLinkHeaderSubscriber::onResponse
   */
  public function testRelatedLinkHeader() {
    // Create a test user that can see media.
    $account = $this->drupalCreateUser([
      'view media',
    ]);
    $this->drupalLogin($account);

    // Visit the other, there should not be a header since it does not even
    // have the field.
    $this->drupalGet('node/' . $this->other->id());
    $this->assertTrue(
      $this->doesNotHaveLinkHeader('related'),
      "Node that does not have entity reference field must not return related link header."
    );

    // Visit the referenced node, there should not be a header since its
    // entity reference field is empty.
    $this->drupalGet('node/' . $this->referenced->id());
    $this->assertTrue(
      $this->doesNotHaveLinkHeader('related'),
      "Node that has empty entity reference field must not return link header."
    );

    // Visit the referencer. It should return a rel="related" link header
    // for both the referenced node and media entity.
    $this->drupalGet('node/' . $this->referencer->id());
    $this->assertTrue(
      $this->validateLinkHeaderWithEntity('related', $this->referenced, 'Referenced Entity') == 1,
      "Malformed related link header"
    );
    $this->assertTrue(
      $this->validateLinkHeaderWithEntity('related', $this->media, 'Media Entity') == 1,
      "Malformed related link header"
    );

    // Log in as anonymous.
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);

    // Visit the referencer. It should return a rel="related" link header
    // for both the referenced node and media entity.
    $this->drupalGet('node/' . $this->referencer->id());
    $this->assertTrue(
      $this->validateLinkHeaderWithEntity('related', $this->referenced, 'Referenced Entity') == 1,
      "Malformed related link header"
    );
    $this->assertTrue(
      $this->validateLinkHeaderWithEntity('related', $this->media, 'Media Entity') == 0,
      "Anonymous should not be able to see media link header"
    );
  }

}
