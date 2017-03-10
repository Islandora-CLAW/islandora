<?php

namespace Drupal\Tests\islandora\Kernel;

use Drupal\islandora\Entity\FedoraResource;
use Drupal\simpletest\UserCreationTrait;

/**
 * Tests for adding, removing and testing for parents.
 *
 * @group islandora
 * @coversDefaultClass \Drupal\islandora\Entity\FedoraResource
 */
class FedoraResourceParentTest extends IslandoraKernelTestBase {

  use UserCreationTrait;

  /**
   * A Drupal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * A Fedora Resource.
   *
   * @var \Drupal\islandora\Entity\FedoraResource
   */
  protected $entity;

  /**
   * Another Fedora Resource.
   *
   * @var \Drupal\islandora\Entity\FedoraResource
   */
  protected $parentEntity;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $permissions = [
      'add fedora resource entities',
      'edit fedora resource entities',
      'delete fedora resource entities',
    ];
    $this->assertTrue($this->checkPermissions($permissions), 'Permissions are invalid');

    $this->user = $this->createUser($permissions);

    $this->entity = FedoraResource::create([
      'type' => 'rdf_source',
      'uid' => $this->user->get('uid'),
      'name' => 'Test Entity',
      'langcode' => 'und',
      'status' => 1,
    ]);
    $this->entity->save();

    $this->parentEntity = FedoraResource::create([
      'type' => 'rdf_source',
      'uid' => $this->user->get('uid'),
      'name' => 'Parent Entity',
      'langcode' => 'und',
      'status' => 1,
    ]);
    $this->parentEntity->save();
  }

  /**
   * @covers \Drupal\islandora\Entity\FedoraResource::setParent
   */
  public function testSetParent() {
    $this->assertTrue($this->entity->get('fedora_has_parent')->isEmpty(), "Entity has an unexpected parent.");

    $this->entity->setParent($this->parentEntity);
    $this->entity->save();

    $this->assertFalse($this->entity->get('fedora_has_parent')->isEmpty(), "Entity has no parent.");
  }

  /**
   * @covers \Drupal\islandora\Entity\FedoraResource::removeParent
   */
  public function testRemoveParent() {
    $this->assertTrue($this->entity->get('fedora_has_parent')->isEmpty(), "Entity has an unexpected parent.");

    $this->entity->set('fedora_has_parent', $this->parentEntity);
    $this->entity->save();

    $this->assertFalse($this->entity->get('fedora_has_parent')->isEmpty(), "Entity has no parent.");

    $this->entity->removeParent();
    $this->entity->save();

    $this->assertTrue($this->entity->get('fedora_has_parent')->isEmpty(), "Entity has an unexpected parent.");
  }

  /**
   * @covers \Drupal\islandora\Entity\FedoraResource::hasParent
   */
  public function testHasParent() {
    $this->assertTrue($this->entity->get('fedora_has_parent')->isEmpty(), "Entity has an unexpected parent.");
    $this->assertFalse($this->entity->hasParent(), "hasParent is reporting a parent incorrectly.");

    $this->entity->set('fedora_has_parent', $this->parentEntity);
    $this->entity->save();

    $this->assertFalse($this->entity->get('fedora_has_parent')->isEmpty(), "Entity has no parent.");
    $this->assertTrue($this->entity->hasParent(), "hasParent is reporting NO parent incorrectly.");

    $this->entity->set('fedora_has_parent', NULL);
    $this->entity->save();

    $this->assertTrue($this->entity->get('fedora_has_parent')->isEmpty(), "Entity still has a parent.");
    $this->assertFalse($this->entity->hasParent(), "hasParent is reporting a parent incorrectly.");
  }

  /**
   * @covers \Drupal\islandora\Entity\FedoraResource::getParentId
   */
  public function testGetParentId() {
    $id = $this->parentEntity->id();

    $this->entity->set('fedora_has_parent', $this->parentEntity);
    $this->entity->save();

    $this->assertEquals($id, $this->entity->getParentId(), "Did not get correct parent id.");
  }

}
