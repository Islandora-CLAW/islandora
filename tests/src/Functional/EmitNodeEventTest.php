<?php

namespace Drupal\Tests\islandora\Functional;

/**
 * Tests the EmitNodeEvent action.
 *
 * @group islandora
 */
class EmitNodeEventTest extends IslandoraFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Set up JWT stuff.
    $key_value = <<<EOD
-----BEGIN RSA PRIVATE KEY-----
MIIEogIBAAKCAQEA6ZT5qNjI4WlXpXzXVuo69MQ0K11V1ZmwW7JaztX0Qsi87JCi
saDIhQps2dEBND2YYKG3AehNFd/a0+ttnKPOnqr13uCVewxpgpPD4lYD0XcCD/U1
pPpOmHYrSOoVtmJvZfr5gQQb0izNM/k0wrO5r5UZzsDPX343HQuiBXzFJtIKau3n
TKjjqs5ErdnftmqsnDhI28yUtlwfSjaRVBIevIT5LGmAboWDukHxf9/x1EemvgMG
E9TQL/+JdLs+LiZglJWWeGofkcThGRcTefHe9GqxoBPtwf/rs6CKN7n3MXGfaxjl
r/dKjJ8Lg5NCrINLUFcNNZippDWIUvj/8lLBXwIDAQABAoIBABmwsOTJMw7XrzQc
TvLYQDO7gKFkWpRrmuH689Hb5kmSGnVKUxqGPIelZeNvAVrli2TVZHNpQVEulbrJ
If0gZxE8bF5fBRHLg69A4UJ7g1/+XtOyfHvwq8RI+unCFTFCEk59FAQEl6q+ErOs
rQjdC4csNvJucmBmWVlwdhl0Z5qlOX3EN/ZXCDnTJsKz75mfa8LC+izXaSv+Gesp
h80wc2V/O9H32djCuz/Ct3WLdHCTQuTiZ32fZAILk/AlZHCHjki5PaLHxAySTmo6
FmJ09/ns0EGuaa1IZz98xLn0yAfAX+MGfsWTsKzAxTO1FcMWvj23mAbwD3Q65ayv
ieMWGwECgYEA/QNKuofgfXu95H+IQMn4l/8zXPt4SdGqGxD5/SlOSi292buoJOF/
eLLlDwsHjQ3+XeFXHHgRyGxD7ZyYe5urFxYrabXlNCIidNVhQAgu31i866cs/Sy4
z0UOzVk5ZCQdvx77/Av8Xe5SBVir54KGRa6h+QMnh7DZNHM3Yha+y+8CgYEA7Fb0
hDCA2YJ6Vb6PeqRPyzsKJP4bQNP1JSM8eThk6RZ/ecAuU9uQjjUuB/O/UeEBRt4w
KUCYoyHLTraPs98N8I000SCoejLjqpyf7SOB2LjGIYPjaTTiXlqJoewWPV5KOoeN
pd+PTTTWeRSpFGjnqkSXCpa8e933raxtkLHPsZECgYBhBl4l4e1osYdElNN/ZPR7
9VWRFq4uQMTm1D/JoYlwUNI5KQl1+zOS6aeFeUlQAknFXqC1PiYzobD68c5XuH6H
v+yuAR8AOwbTnvBISdsPs0vfYqCSBhBpC6Z9gPXNPTxbClq/cSk6LCYv/q0NfrRX
DHz4rQj/tAXXY0edyfMo6QKBgGgBqF+YHMwb4IxlbSzyrG7qj39SGFpCLOroA8/w
4m+1R+ojif+7a3U5sAUt3m9BDtfKJfWxiLqZv6fnLXxh1/eZnLm/noUQaiKGBNdO
PfFK915+dRCyhkAxpcoNZIgjO5VgXBS4Oo8mhpAIaJQjynei8blmNpJoT3wtmpYH
ujgRAoGALyTXD/v/kxkE+31rmga1JM2IyjVwzefmqcdzNo3e8KovtZ79FJNfgcEx
FZTd3w207YHqKu/CX/BF15kfIOh03t+0AEUyKUTY5JWS84oQPU6td1DOSA6P36xl
EOLIc/4JOdONrJKWYpWIjDhHLL8BacjLoh2bDY0KdYa69AfYvW4=
-----END RSA PRIVATE KEY-----
EOD;

    $key = $this->container->get('entity_type.manager')->getStorage('key')->create([
      'id' => 'test',
      'label' => 'Test',
      'key_type' => 'jwt_rs',
      'key_type_settings' => [
        'algorithm' => 'RS256',
      ],
      'key_provider' => 'config',
      'key_provider_settings' => [
        'key_value' => $key_value,
      ],
    ]);
    $key->save();

    $jwt_config = $this->container->get('config.factory')->getEditable('jwt.config');
    $jwt_config->set('algorithm', 'RS256');
    $jwt_config->set('key_id', 'test');
    $jwt_config->save(TRUE);
  }

  /**
   * @covers \Drupal\islandora\ContextProvider\NodeContextProvider::__construct
   * @covers \Drupal\islandora\ContextProvider\NodeContextProvider::getRuntimeContexts
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::buildConfigurationForm
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::submitConfigurationForm
   * @covers \Drupal\islandora\EventGenerator\EmitEvent::execute
   * @covers \Drupal\islandora\EventGenerator\EventGenerator::generateCreateEvent
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
    $action_id = $this->createEmitAction('node', 'create');

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
   * @covers \Drupal\islandora\EventGenerator\EventGenerator::generateCreateEvent
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
    $action_id = $this->createEmitAction('media', 'create');

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
   * @covers \Drupal\islandora\EventGenerator\EventGenerator::generateCreateEvent
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
    $action_id = $this->createEmitAction('file', 'create');

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
   * @covers \Drupal\islandora\EventGenerator\EventGenerator::generateUpdateEvent
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
    $action_id = $this->createEmitAction('node', 'update');

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
   * @covers \Drupal\islandora\EventGenerator\EventGenerator::generateUpdateEvent
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
    $action_id = $this->createEmitAction('media', 'update');

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
   * @covers \Drupal\islandora\EventGenerator\EventGenerator::generateUpdateEvent
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
    $action_id = $this->createEmitAction('file', 'update');

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
   * @covers \Drupal\islandora\EventGenerator\EventGenerator::generateDeleteEvent
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
    $action_id = $this->createEmitAction('node', 'delete');

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
   * @covers \Drupal\islandora\EventGenerator\EventGenerator::generateDeleteEvent
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
    $action_id = $this->createEmitAction('media', 'delete');

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
   * @covers \Drupal\islandora\EventGenerator\EventGenerator::generateDeleteEvent
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
    $action_id = $this->createEmitAction('file', 'delete');

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

    $action_id = "emit_" . $entity_type . "_" . $event_type;
    $this->getSession()->getPage()->fillField('edit-label', "Emit $entity_type $event_type");
    $this->getSession()->getPage()->fillField('edit-id', $action_id);
    $this->getSession()->getPage()->fillField('edit-queue', "emit-$entity_type-$event_type");
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
