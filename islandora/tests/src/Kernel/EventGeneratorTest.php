<?php

namespace Drupal\Tests\islandora\Kernel;

use Drupal\islandora\Entity\FedoraResource;
use Drupal\islandora\EventGenerator\EventGenerator;
use Drupal\KernelTests\KernelTestBase;
use Drupal\simpletest\UserCreationTrait;

/**
 * Tests the EventGenerator default implementation.
 *
 * @group islandora
 */
class EventGeneratorTest extends KernelTestBase {

  use UserCreationTrait {
    createUser as drupalCreateUser;
  }

  /**
   * User entity.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Fedora resource entity.
   *
   * @var \Drupal\islandora\FedoraResourceInterface
   */
  protected $entity;

  /**
   * The EventGenerator to test.
   *
   * @var \Drupal\islandora\EventGenerator\EventGeneratorInterface
   */
  protected $eventGenerator;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'field',
    'filter',
    'block',
    'node',
    'path',
    'text',
    'options',
    'inline_entity_form',
    'serialization',
    'rest',
    'rdf',
    'jsonld',
    'islandora'
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Bootstrap minimal Drupal environment to run the tests.
    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('user');
    $this->installConfig('filter');
    $this->installEntitySchema('fedora_resource');

    // Create a test user.
    $this->user = $this->drupalCreateUser();

    // Create a test entity.
    $this->entity = FedoraResource::create([
      "type" => "rdf_source",
      "uid" => 1,
      "name" => "Test Fixture",
      "langcode" => "und",
      "status" => 1,
    ]); 
    $this->entity->save();

    // Create the event generator so we can test it.
    $this->eventGenerator = new EventGenerator();
  }

  /**
   * Tests the generateCreateEvent() method.
   */
  public function testGenerateCreateEvent() {
    $json = $this->eventGenerator->generateCreateEvent($this->entity, $this->user);
    $msg = json_decode($json, TRUE);

    $this->assertBasicStructure($msg);
    $this->assertTrue($msg["type"] == "Create", "Event type is 'Create'.");
  }

  /**
   * Tests the generateUpdateEvent() method.
   */
  public function testGenerateUpdateEvent() {
    $json = $this->eventGenerator->generateUpdateEvent($this->entity, $this->user);
    $msg = json_decode($json, TRUE);

    $this->assertBasicStructure($msg);
    $this->assertTrue($msg["type"] == "Update", "Event type is 'Update'.");
  }

  /**
   * Tests the generateDeleteEvent() method.
   */
  public function testGenerateDeleteEvent() {
    $json = $this->eventGenerator->generateDeleteEvent($this->entity, $this->user);
    $msg = json_decode($json, TRUE);

    $this->assertBasicStructure($msg);
    $this->assertTrue($msg["type"] == "Delete", "Event type is 'Delete'.");

  }

  /**
   * Util function for repeated checks.
   *
   * @param array $msg
   *   The message parsed as an array.
   */
  protected function assertBasicStructure(array $msg) {
    // Looking for @context.
    $this->assertTrue(array_key_exists('@context', $msg), "Context key exists");
    $this->assertTrue($msg["@context"] == "https://www.w3.org/ns/activitystreams", "Context is activity stream.");

    // Make sure it has a type.
    $this->assertTrue(array_key_exists('type', $msg), "Type key exists");

    // Make sure the actor exists, is a person, and has a uri.
    $this->assertTrue(array_key_exists('actor', $msg), "Actor key exists");
    $this->assertTrue(array_key_exists("type", $msg["actor"]), "Type key exists for actor.");
    $this->assertTrue($msg["actor"]["type"] == "Person", "Actor is a person.");
    $this->assertTrue(array_key_exists("id", $msg["actor"]), "Id key exists for actor.");
    $this->assertTrue($msg["actor"]["id"] == $this->user->toUrl()->setAbsolute()->toString(), "Id is user's uri");

    // Make sure the object exists and is a uri.
    $this->assertTrue(array_key_exists('object', $msg), "Object key exists");
    $this->assertTrue($msg["object"] == $this->entity->toUrl()->setAbsolute()->toString(), "Object is entity uri.");
  }

}
