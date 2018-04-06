<?php

namespace Drupal\Tests\islandora\Functional;

use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\Tests\media_entity\Functional\MediaEntityFunctionalTestTrait;

/**
 * Tests the IsReferencedMedia condition.
 *
 * @group islandora
 */
class IsReferencedMediaTest extends IslandoraFunctionalTestBase {

  use EntityReferenceTestTrait;
  use MediaEntityFunctionalTestTrait;

  /**
   * Media to be referenced.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $referenced;

  /**
   * An unreferenced Media to use as a control.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $notReferenced;

  /**
   * A Media referenced by another type the Condition is not set to expect.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $referencedByAnother;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $another_test_type_with_reference = $this->container->get('entity_type.manager')->getStorage('node_type')->create([
      'type' => 'another_test_type_with_reference',
      'label' => 'Another Test Type With Reference',
    ]);
    $another_test_type_with_reference->save();
    $this->createEntityReferenceField('node', 'another_test_type_with_reference', 'field_media', 'Referenced Media', 'media', 'default', [], 2);

    // Create the media.
    $media_bundle = $this->drupalCreateMediaBundle();
    $this->referenced = $this->container->get('entity_type.manager')->getStorage('media')->create([
      'bundle' => $media_bundle->id(),
      'name' => 'Referenced Media',
    ]);
    $this->referenced->save();

    $this->notReferenced = $this->container->get('entity_type.manager')->getStorage('media')->create([
      'bundle' => $media_bundle->id(),
      'name' => 'Unreferenced Media',
    ]);
    $this->notReferenced->save();

    $this->referencedByAnother = $this->container->get('entity_type.manager')->getStorage('media')->create([
      'bundle' => $media_bundle->id(),
      'name' => 'Referenced By Another',
    ]);
    $this->referencedByAnother->save();

    // Reference one by a node of the type we're expecting.
    $node = $this->container->get('entity_type.manager')->getStorage('node')->create([
      'type' => 'test_type_with_reference',
      'title' => 'Referencer',
      'field_media' => [$this->referenced->id()],
    ]);
    $node->save();

    // Reference one by a node of the type we're not expecting.
    $another_node = $this->container->get('entity_type.manager')->getStorage('node')->create([
      'type' => 'another_test_type_with_reference',
      'title' => 'Another Referencer',
      'field_media' => [$this->referencedByAnother->id()],
    ]);
    $node->save();
  }

  /**
   * @covers \Drupal\islandora\ContextProvider\MediaContextProvider::__construct
   * @covers \Drupal\islandora\ContextProvider\MediaContextProvider::getRuntimeContexts
   * @covers \Drupal\islandora\IslandoraContextManager::evaluateContexts
   * @covers \Drupal\islandora\IslandoraContextManager::applyContexts
   * @covers \Drupal\islandora\Plugin\Condition\IsReferencedMedia::buildConfigurationForm
   * @covers \Drupal\islandora\Plugin\Condition\IsReferencedMedia::submitConfigurationForm
   * @covers \Drupal\islandora\Plugin\Condition\IsReferencedMedia::evaluate
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::buildConfigurationForm
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::submitConfigurationForm
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::execute
   * @covers \Drupal\islandora\IslandoraServiceProvider::alter
   */
  public function testIsReferencedMedia() {
    // Create a test user.
    $account = $this->drupalCreateUser([
      'administer contexts',
      'view media',
      'update any media',
    ]);
    $this->drupalLogin($account);

    $this->createContext('Test', 'test');

    // Add the condition.
    $this->drupalGet("admin/structure/context/test/condition/add/is_referenced_media");
    $this->getSession()->getPage()->findById("edit-conditions-is-referenced-media-field")->selectOption('test_type_with_reference|field_media');
    $this->getSession()->getPage()->pressButton('Save and continue');

    // Add the reaction to say "Hello World!".
    $this->addPresetReaction('test', 'index', 'hello_world');

    // Edit the referenced node.  "Hello World!" should be output to the screen.
    $this->postEntityEditForm("media/{$this->referenced->id()}", ['name[0][value]' => 'Referenced Media Changed'], 'Save and keep published');
    $this->assertSession()->pageTextContains("Hello World!");

    // Edit the unreferenced node.  "Hello World!" should not be output to the
    // screen.
    $this->postEntityEditForm("media/{$this->notReferenced->id()}", ['name[0][value]' => 'Unreferenced Media Changed'], 'Save and keep published');
    $this->assertSession()->pageTextNotContains("Hello World!");

    // Edit the node referenced by a different type.  "Hello World!" should not
    // be output to the screen.
    $this->postEntityEditForm("media/{$this->referencedByAnother->id()}", ['name[0][value]' => 'Referenced By Another Changed'], 'Save and keep published');
    $this->assertSession()->pageTextNotContains("Hello World!");
  }

}
