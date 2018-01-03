<?php

namespace Drupal\Tests\islandora\Functional;

/**
 * Tests the IsNode condition.
 *
 * @group islandora
 */
class IsNodeTest extends IslandoraFunctionalTestBase {

  /**
   * @covers \Drupal\islandora\ContextProvider\NodeContextProvider::__construct
   * @covers \Drupal\islandora\ContextProvider\NodeContextProvider::getRuntimeContexts
   * @covers \Drupal\islandora\ContextProvider\MediaContextProvider::__construct
   * @covers \Drupal\islandora\ContextProvider\MediaContextProvider::getRuntimeContexts
   * @covers \Drupal\islandora\IslandoraContextManager::evaluateContexts
   * @covers \Drupal\islandora\IslandoraContextManager::applyContexts
   * @covers \Drupal\islandora\Plugin\Condition\IsNode::evaluate
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::buildConfigurationForm
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::submitConfigurationForm
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::execute
   * @covers \Drupal\islandora\IslandoraServiceProvider::alter
   */
  public function testIsNode() {
    // Create a test user.
    $account = $this->drupalCreateUser([
      'bypass node access',
      'administer contexts',
      'view media',
      'create media',
      'update media',
    ]);
    $this->drupalLogin($account);

    $this->createContext('Test', 'test');
    $this->addCondition('test', 'is_node');
    $this->addPresetReaction('test', 'index', 'hello_world');

    // Create a new node confirm Hello World! is printed to the screen.
    $this->postNodeAddForm('test_type', ['title[0][value]' => 'Test Node'], 'Save');
    $this->assertSession()->pageTextContains("Hello World!");

    // Add a new Thumbnail media and confirm Hello World! is not printed to the
    // screen.
    $this->createThumbnailWithFile();
    $this->assertSession()->pageTextNotContains("Hello World!");
  }

}
