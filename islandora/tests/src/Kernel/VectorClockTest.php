<?php

namespace Drupal\Tests\islandora\Kernel;

use Drupal\islandora\Entity\FedoraResource;
use Drupal\simpletest\UserCreationTrait;

/**
 * Tests the basic behavior of a vector clock.
 *
 * @group islandora
 */
class VectorClockTest extends IslandoraKernelTestBase {

  /**
   * Fedora resource entity.
   *
   * @var \Drupal\islandora\FedoraResourceInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a test entity.
    $this->entity = FedoraResource::create([
      "type" => "rdf_source",
      "uid" => 1,
      "name" => "Test Fixture",
      "langcode" => "und",
      "status" => 1,
    ]); 
    $this->entity->save();

  }

  /**
   * Tests the basic behavior of the vector clock.
   */
  public function testVectorClock() {
    // Check the vclock is set to 0 when a new entity is created.
    $this->assertTrue($this->entity->getVclock() == 0, "Vector clock initialized to zero.");
    
    // Edit the entity.
    $this->entity->setName("Edited Test Fixture")->save();

    // Check the vclock is incremented when the entity is updated.
    $this->assertTrue($this->entity->getVclock() == 1, "Vector clock incremented on update.");
  }

}
