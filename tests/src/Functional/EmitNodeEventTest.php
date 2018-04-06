<?php

namespace Drupal\Tests\islandora\Functional;

/**
 * Tests the EmitNodeEvent action.
 *
 * @group islandora
 */
class EmitNodeEventTest extends IslandoraFunctionalTestBase {

  /**
   * @covers \Drupal\islandora\ContextProvider\NodeContextProvider::__construct
   * @covers \Drupal\islandora\ContextProvider\NodeContextProvider::getRuntimeContexts
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::buildConfigurationForm
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::submitConfigurationForm
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::execute
   * @covers \Drupal\islandora\EventGenerator\EventGenerator::generateEvent
   * @covers \Drupal\islandora\IslandoraContextManager::evaluateContexts
   * @covers \Drupal\islandora\IslandoraContextManager::applyContexts
   * @covers \Drupal\islandora\Plugin\Condition\IsNode::evaluate
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::buildConfigurationForm
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::submitConfigurationForm
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::execute
   * @covers \Drupal\islandora\IslandoraServiceProvider::alter
   */
  public function testNodeCreateEvent() {
    // Create a test user.
    $account = $this->drupalCreateUser([
      'bypass node access',
      'administer contexts',
      'administer actions',
    ]);
    $this->drupalLogin($account);

    // Create an action to emit a node event.
    $action_id = $this->createEmitAction('node', 'Create');

    // Create a context and add the action as an index reaction.
    $this->createContext('Test', 'test');
    $this->addCondition('test', 'is_node');
    $this->addPresetReaction('test', 'index', $action_id);
    $this->assertSession()->statusCodeEquals(200);

    // Create a new node, which publishes the create event.
    $this->postNodeAddForm('test_type', ['title[0][value]' => 'Test Node'], 'Save');

    // Validate the message actually gets sent.
    $queue = str_replace('_', '-', $action_id);
    $this->verifyMessageIsSent($queue, 'Create');
  }

  /**
   * @covers \Drupal\islandora\ContextProvider\MediaContextProvider::__construct
   * @covers \Drupal\islandora\ContextProvider\MediaContextProvider::getRuntimeContexts
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::buildConfigurationForm
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::submitConfigurationForm
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::execute
   * @covers \Drupal\islandora\EventGenerator\EventGenerator::generateEvent
   * @covers \Drupal\islandora\IslandoraContextManager::evaluateContexts
   * @covers \Drupal\islandora\IslandoraContextManager::applyContexts
   * @covers \Drupal\islandora\Plugin\Condition\IsMedia::evaluate
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::buildConfigurationForm
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::submitConfigurationForm
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::execute
   * @covers \Drupal\islandora\IslandoraServiceProvider::alter
   */
  public function testMediaCreateEvent() {
    // Create a test user.
    $account = $this->drupalCreateUser([
      'administer contexts',
      'administer actions',
      'view media',
      'create media',
    ]);
    $this->drupalLogin($account);

    // Create an action to emit a media event.
    $action_id = $this->createEmitAction('media', 'Create');

    // Create a context and add the action as an index reaction.
    $this->createContext('Test', 'test');
    $this->addCondition('test', 'is_media');
    $this->addPresetReaction('test', 'index', $action_id);
    $this->assertSession()->statusCodeEquals(200);

    // Create a new media, which publishes the create event.
    $this->createThumbnailWithFile();

    // Validate the message actually gets sent.
    $queue = str_replace('_', '-', $action_id);
    $this->verifyMessageIsSent($queue, 'Create');
  }

  /**
   * @covers \Drupal\islandora\ContextProvider\FileContextProvider::__construct
   * @covers \Drupal\islandora\ContextProvider\FileContextProvider::getRuntimeContexts
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::buildConfigurationForm
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::submitConfigurationForm
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::execute
   * @covers \Drupal\islandora\EventGenerator\EventGenerator::generateEvent
   * @covers \Drupal\islandora\IslandoraContextManager::evaluateContexts
   * @covers \Drupal\islandora\IslandoraContextManager::applyContexts
   * @covers \Drupal\islandora\Plugin\Condition\IsFile::evaluate
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::buildConfigurationForm
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::submitConfigurationForm
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::execute
   * @covers \Drupal\islandora\IslandoraServiceProvider::alter
   */
  public function testFileCreateEvent() {
    // Create a test user.
    $account = $this->drupalCreateUser([
      'administer contexts',
      'administer actions',
      'view media',
      'create media',
    ]);
    $this->drupalLogin($account);

    // Create an action to emit a media event.
    $action_id = $this->createEmitAction('file', 'Create');

    // Create a context and add the action as an index reaction.
    $this->createContext('Test', 'test');
    $this->addCondition('test', 'is_file');
    $this->addPresetReaction('test', 'index', $action_id);
    $this->assertSession()->statusCodeEquals(200);

    // Create a new file, which publishes the create event.
    $this->createThumbnailWithFile();

    // Validate the message actually gets sent.
    $queue = str_replace('_', '-', $action_id);
    $this->verifyMessageIsSent($queue, 'Create');
  }

  /**
   * @covers \Drupal\islandora\ContextProvider\NodeContextProvider::__construct
   * @covers \Drupal\islandora\ContextProvider\NodeContextProvider::getRuntimeContexts
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::buildConfigurationForm
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::submitConfigurationForm
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::execute
   * @covers \Drupal\islandora\EventGenerator\EventGenerator::generateEvent
   * @covers \Drupal\islandora\IslandoraContextManager::evaluateContexts
   * @covers \Drupal\islandora\IslandoraContextManager::applyContexts
   * @covers \Drupal\islandora\Plugin\Condition\IsNode::evaluate
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::buildConfigurationForm
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::submitConfigurationForm
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::execute
   * @covers \Drupal\islandora\IslandoraServiceProvider::alter
   */
  public function testNodeUpdateEvent() {
    // Create a test user.
    $account = $this->drupalCreateUser([
      'bypass node access',
      'administer contexts',
      'administer actions',
    ]);
    $this->drupalLogin($account);

    // Create an action to emit a node event.
    $action_id = $this->createEmitAction('node', 'Update');

    // Create a context and add the action as an index reaction.
    $this->createContext('Test', 'test');
    $this->addCondition('test', 'is_node');
    $this->addPresetReaction('test', 'index', $action_id);
    $this->assertSession()->statusCodeEquals(200);

    // Create a new node, which publishes the update event.
    $this->postNodeAddForm('test_type', ['title[0][value]' => 'Test Node'], 'Save');

    $queue = str_replace('_', '-', $action_id);
    $this->verifyMessageIsSent($queue, 'Update');
  }

  /**
   * @covers \Drupal\islandora\ContextProvider\MediaContextProvider::__construct
   * @covers \Drupal\islandora\ContextProvider\MediaContextProvider::getRuntimeContexts
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::buildConfigurationForm
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::submitConfigurationForm
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::execute
   * @covers \Drupal\islandora\EventGenerator\EventGenerator::generateEvent
   * @covers \Drupal\islandora\IslandoraContextManager::evaluateContexts
   * @covers \Drupal\islandora\IslandoraContextManager::applyContexts
   * @covers \Drupal\islandora\Plugin\Condition\IsMedia::evaluate
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::buildConfigurationForm
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::submitConfigurationForm
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::execute
   * @covers \Drupal\islandora\IslandoraServiceProvider::alter
   */
  public function testMediaUpdateEvent() {
    // Create a test user.
    $account = $this->drupalCreateUser([
      'administer contexts',
      'administer actions',
      'view media',
      'create media',
    ]);
    $this->drupalLogin($account);

    // Create an action to emit a media event.
    $action_id = $this->createEmitAction('media', 'Update');

    // Create a context and add the action as an index reaction.
    $this->createContext('Test', 'test');
    $this->addCondition('test', 'is_media');
    $this->addPresetReaction('test', 'index', $action_id);
    $this->assertSession()->statusCodeEquals(200);

    // Create a new media, which publishes the update event.
    $this->createThumbnailWithFile();

    // Validate the message actually gets sent.
    $queue = str_replace('_', '-', $action_id);
    $this->verifyMessageIsSent($queue, 'Update');
  }

  /**
   * @covers \Drupal\islandora\ContextProvider\FileContextProvider::__construct
   * @covers \Drupal\islandora\ContextProvider\FileContextProvider::getRuntimeContexts
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::buildConfigurationForm
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::submitConfigurationForm
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::execute
   * @covers \Drupal\islandora\EventGenerator\EventGenerator::generateEvent
   * @covers \Drupal\islandora\IslandoraContextManager::evaluateContexts
   * @covers \Drupal\islandora\IslandoraContextManager::applyContexts
   * @covers \Drupal\islandora\Plugin\Condition\IsFile::evaluate
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::buildConfigurationForm
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::submitConfigurationForm
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::execute
   * @covers \Drupal\islandora\IslandoraServiceProvider::alter
   */
  public function testFileUpdateEvent() {
    // Create a test user.
    $account = $this->drupalCreateUser([
      'administer contexts',
      'administer actions',
      'view media',
      'create media',
    ]);
    $this->drupalLogin($account);

    // Create an action to emit a media event.
    $action_id = $this->createEmitAction('file', 'Update');

    // Create a context and add the action as an index reaction.
    $this->createContext('Test', 'test');
    $this->addCondition('test', 'is_file');
    $this->addPresetReaction('test', 'index', $action_id);
    $this->assertSession()->statusCodeEquals(200);

    // Create a new file, which publishes the update event.
    $this->createThumbnailWithFile();

    // Validate the message actually gets sent.
    $queue = str_replace('_', '-', $action_id);
    $this->verifyMessageIsSent($queue, 'Update');
  }

  /**
   * @covers \Drupal\islandora\ContextProvider\NodeContextProvider::__construct
   * @covers \Drupal\islandora\ContextProvider\NodeContextProvider::getRuntimeContexts
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::buildConfigurationForm
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::submitConfigurationForm
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::execute
   * @covers \Drupal\islandora\EventGenerator\EventGenerator::generateEvent
   * @covers \Drupal\islandora\IslandoraContextManager::evaluateContexts
   * @covers \Drupal\islandora\IslandoraContextManager::applyContexts
   * @covers \Drupal\islandora\Plugin\Condition\IsNode::evaluate
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::buildConfigurationForm
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::submitConfigurationForm
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::execute
   * @covers \Drupal\islandora\IslandoraServiceProvider::alter
   */
  public function testNodeDeleteEvent() {
    // Create a test user.
    $account = $this->drupalCreateUser([
      'bypass node access',
      'administer contexts',
      'administer actions',
    ]);
    $this->drupalLogin($account);

    // Create an action to emit a node event.
    $action_id = $this->createEmitAction('node', 'Delete');

    // Create a context and add the action as an index reaction.
    $this->createContext('Test', 'test');
    $this->addCondition('test', 'is_node');
    $this->addPresetReaction('test', 'index', $action_id);
    $this->assertSession()->statusCodeEquals(200);

    // Create a new node, which publishes the delete event (lol).
    $this->postNodeAddForm('test_type', ['title[0][value]' => 'Test Node'], 'Save');

    $queue = str_replace('_', '-', $action_id);
    $this->verifyMessageIsSent($queue, 'Delete');
  }

  /**
   * @covers \Drupal\islandora\ContextProvider\MediaContextProvider::__construct
   * @covers \Drupal\islandora\ContextProvider\MediaContextProvider::getRuntimeContexts
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::buildConfigurationForm
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::submitConfigurationForm
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::execute
   * @covers \Drupal\islandora\EventGenerator\EventGenerator::generateEvent
   * @covers \Drupal\islandora\IslandoraContextManager::evaluateContexts
   * @covers \Drupal\islandora\IslandoraContextManager::applyContexts
   * @covers \Drupal\islandora\Plugin\Condition\IsMedia::evaluate
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::buildConfigurationForm
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::submitConfigurationForm
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::execute
   * @covers \Drupal\islandora\IslandoraServiceProvider::alter
   */
  public function testMediaDeleteEvent() {
    // Create a test user.
    $account = $this->drupalCreateUser([
      'administer contexts',
      'administer actions',
      'view media',
      'create media',
    ]);
    $this->drupalLogin($account);

    // Create an action to emit a media event.
    $action_id = $this->createEmitAction('media', 'Delete');

    // Create a context and add the action as an index reaction.
    $this->createContext('Test', 'test');
    $this->addCondition('test', 'is_media');
    $this->addPresetReaction('test', 'index', $action_id);
    $this->assertSession()->statusCodeEquals(200);

    // Create a new media, which publishes the delete event.
    $this->createThumbnailWithFile();

    // Validate the message actually gets sent.
    $queue = str_replace('_', '-', $action_id);
    $this->verifyMessageIsSent($queue, 'Delete');
  }

  /**
   * @covers \Drupal\islandora\ContextProvider\FileContextProvider::__construct
   * @covers \Drupal\islandora\ContextProvider\FileContextProvider::getRuntimeContexts
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::buildConfigurationForm
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::submitConfigurationForm
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::execute
   * @covers \Drupal\islandora\EventGenerator\EventGenerator::generateEvent
   * @covers \Drupal\islandora\IslandoraContextManager::evaluateContexts
   * @covers \Drupal\islandora\IslandoraContextManager::applyContexts
   * @covers \Drupal\islandora\Plugin\Condition\IsFile::evaluate
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::buildConfigurationForm
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::submitConfigurationForm
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::execute
   * @covers \Drupal\islandora\IslandoraServiceProvider::alter
   */
  public function testFileDeleteEvent() {
    // Create a test user.
    $account = $this->drupalCreateUser([
      'administer contexts',
      'administer actions',
      'view media',
      'create media',
    ]);
    $this->drupalLogin($account);

    // Create an action to emit a media event.
    $action_id = $this->createEmitAction('file', 'Delete');

    // Create a context and add the action as an index reaction.
    $this->createContext('Test', 'test');
    $this->addCondition('test', 'is_file');
    $this->addPresetReaction('test', 'index', $action_id);
    $this->assertSession()->statusCodeEquals(200);

    // Create a new file, which publishes the delete event.
    $this->createThumbnailWithFile();

    // Validate the message actually gets sent.
    $queue = str_replace('_', '-', $action_id);
    $this->verifyMessageIsSent($queue, 'Delete');
  }

  /**
   * Utility function to create an emit action.
   *
   * @param string $entity_type
   *   Entity type id.
   * @param string $event_type
   *   Event type (create, update, or delete).
   */
  protected function createEmitAction($entity_type, $event_type) {
    $this->drupalGet('admin/config/system/actions');
    $this->getSession()->getPage()->findById("edit-action")->selectOption("Emit a $entity_type event to a queue/topic...");
    $this->getSession()->getPage()->pressButton(t('Create'));
    $this->assertSession()->statusCodeEquals(200);

    $action_id = "emit_" . $entity_type . "_" . lcfirst($event_type);
    $this->getSession()->getPage()->fillField('edit-label', "Emit $entity_type " . lcfirst($event_type));
    $this->getSession()->getPage()->fillField('edit-id', $action_id);
    $this->getSession()->getPage()->fillField('edit-queue', "emit-$entity_type-" . lcfirst($event_type));
    $this->getSession()->getPage()->findById("edit-event")->selectOption($event_type);
    $this->getSession()->getPage()->pressButton(t('Save'));
    $this->assertSession()->statusCodeEquals(200);

    return $action_id;
  }

  /**
   * Asserts the message was delivered and checks its general shape.
   *
   * @param string $queue
   *   The queue to check for the message.
   * @param string $event_type
   *   Event type (create, update, or delete).
   */
  protected function verifyMessageIsSent($queue, $event_type) {
    // Verify message is sent.
    $stomp = $this->container->get('islandora.stomp');
    try {
      $stomp->subscribe($queue);
      while ($msg = $stomp->read()) {
        $headers = $msg->getHeaders();
        $this->assertTrue(
          isset($headers['Authorization']),
          "Authorization header must be set"
        );
        $matches = [];
        $this->assertTrue(
          preg_match('/^Bearer (.*)/', $headers['Authorization'], $matches),
          "Authorization header must be a bearer token"
        );
        $this->assertTrue(
          count($matches) == 2 && !empty($matches[1]),
          "Bearer token must not be empty"
        );

        $body = $msg->getBody();
        $body = json_decode($body, TRUE);
        $type = $body['type'];
        $this->assertTrue($type == $event_type, "Expected $event_type, received $type");
      }
      $stomp->unsubscribe();
    }
    catch (StompException $e) {
      $this->assertTrue(FALSE, "There was an error connecting to the stomp broker");
    }
  }

}
