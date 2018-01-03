<?php

namespace Drupal\Tests\islandora\Functional;

use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;

/**
 * Tests the RelatedLinkHeader view alter.
 *
 * @group islandora
 */
class RelatedLinkHeaderTest extends IslandoraFunctionalTestBase {

  use EntityReferenceTestTrait;

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
   * Node of a bundle that does _not_ have an entity reference field.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $other;

  /**
   * Node with two values for its entity reference field.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $twoReferences;

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

    // Add an entity reference field to it.
    $this->createEntityReferenceField('node', 'test_type_with_reference', 'field_reference', 'Referenced Entity', 'node', 'default', [], 2);

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

    $this->referencer = $this->container->get('entity_type.manager')->getStorage('node')->create([
      'type' => 'test_type_with_reference',
      'title' => 'Referencer',
      'field_reference' => [$this->referenced->id()],
    ]);
    $this->referencer->save();

    // Create a node that references two others.
    $this->twoReferences = $this->container->get('entity_type.manager')->getStorage('node')->create([
      'type' => 'test_type_with_reference',
      'title' => 'Two References',
      'field_reference' => [$this->referenced->id(), $this->other->id()],
    ]);
    $this->twoReferences->save();
  }

  /**
   * @covers \Drupal\islandora\ViewAlter\RelatedLinkHeader::alter
   */
  public function testRelatedLinkHeader() {
    // Create a test user.
    $account = $this->drupalCreateUser([
      'bypass node access',
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

    // Visit the referencer. It should return one rel="related" link header
    // pointing to the referenced node.
    $this->drupalGet('node/' . $this->referencer->id());
    $this->assertTrue(
      $this->validateLinkHeader('related', $this->referenced, 'Referenced Entity') == 1,
      "Malformed related link header"
    );

    // Visit the node with two references.  It should return a rel="related"
    // link header for each referenced node.
    $this->drupalGet('node/' . $this->twoReferences->id());
    $this->assertTrue(
      $this->validateLinkHeader('related', $this->referenced, 'Referenced Entity') == 1,
      "Malformed related link header"
    );
    $this->assertTrue(
      $this->validateLinkHeader('related', $this->other, 'Referenced Entity') == 1,
      "Malformed related link header"
    );
  }

}
