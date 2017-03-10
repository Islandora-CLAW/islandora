<?php

namespace Drupal\Tests\islandora\Kernel;

use Drupal\islandora\Entity\FedoraResource;
use Drupal\simpletest\UserCreationTrait;

/**
 * Tests the clean up of deleted parent referenced from children.
 *
 * @group islandora
 * @coversDefaultClass \Drupal\islandora\Entity\FedoraResource
 */
class DeleteFedoraResourceWithParentsTest extends IslandoraKernelTestBase {

  use UserCreationTrait;

  /**
   * Parent Fedora resource entity.
   *
   * @var \Drupal\islandora\FedoraResourceInterface
   */
  protected $parentEntity;

  /**
   * Child Fedora resource entity.
   *
   * @var \Drupal\islandora\FedoraResourceInterface
   */
  protected $childEntity;

  /**
   * User entity.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

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

    // Create a test entity.
    $this->parentEntity = FedoraResource::create([
      "type" => "rdf_source",
      "uid" => $this->user->get('uid'),
      "name" => "Test Parent",
      "langcode" => "und",
      "status" => 1,
    ]);
    $this->parentEntity->save();

    $this->childEntity = FedoraResource::create([
      "type" => "rdf_source",
      "uid" => $this->user->get('uid'),
      "name" => "Test Child",
      "langcode" => "und",
      "status" => 1,
    ]);
    $this->childEntity->save();
  }

  /**
   * Tests cleaning up child to parent references when parent is deleted.
   *
   * @covers \Drupal\islandora\Entity\FedoraResource::postDelete
   */
  public function testCleanUpParents() {

    $child_id = $this->childEntity->id();

    $this->assertTrue($this->childEntity->get('fedora_has_parent')->isEmpty(), "Should not have a parent.");

    $this->childEntity->set('fedora_has_parent', $this->parentEntity)->save();

    $this->assertFalse($this->childEntity->get('fedora_has_parent')->isEmpty(), "Now we are missing a parent.");

    // This sees the changes from the postDelete, $this->childEntity doesn't.
    $new_child = FedoraResource::load($child_id);

    $this->assertFalse($new_child->get('fedora_has_parent')->isEmpty(), "Now we are missing a parent.");

    $this->parentEntity->delete();

    $this->assertTrue($new_child->get('fedora_has_parent')->isEmpty(), "Child should not have a parent.");

  }

}
