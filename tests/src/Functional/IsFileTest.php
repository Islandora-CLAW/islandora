<?php

namespace Drupal\Tests\islandora\Functional;

/**
 * Tests the IsFile condition.
 *
 * @group islandora
 */
class IsFileTest extends IslandoraFunctionalTestBase {

  /**
   * @covers \Drupal\islandora\ContextProvider\FileContextProvider::__construct
   * @covers \Drupal\islandora\ContextProvider\FileContextProvider::getRuntimeContexts
   * @covers \Drupal\islandora\ContextProvider\MediaContextProvider::__construct
   * @covers \Drupal\islandora\ContextProvider\MediaContextProvider::getRuntimeContexts
   * @covers \Drupal\islandora\IslandoraContextManager::evaluateContexts
   * @covers \Drupal\islandora\IslandoraContextManager::applyContexts
   * @covers \Drupal\islandora\Plugin\Condition\IsFile::evaluate
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::buildConfigurationForm
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::submitConfigurationForm
   * @covers \Drupal\islandora\PresetReaction\PresetReaction::execute
   * @covers \Drupal\islandora\IslandoraServiceProvider::alter
   */
  public function testIsFile() {
    // Create a test user.
    $account = $this->drupalCreateUser([
      'administer contexts',
      'view media',
      'create media',
      'update media',
    ]);
    $this->drupalLogin($account);

    // Set it up.
    $this->createContext('Test', 'test');
    $this->addCondition('test', 'is_file');
    $this->addPresetReaction('test', 'index', 'hello_world');

    // Add a new Thumbnail media and confirm Hello World! is printed to the
    // screen for the file upload.
    $file = current($this->getTestFiles('image'));
    $values = [
      'name[0][value]' => 'Test Media',
      'files[field_image_0]' => \Drupal::service('file_system')->realpath($file->uri),
    ];
    $this->drupalPostForm('media/add/tn', $values, t('Save and publish'));
    $this->assertSession()->pageTextContains("Hello World!");

    $values = [
      'field_image[0][alt]' => 'Alternate text',
    ];
    $this->getSession()->getPage()->fillField('edit-field-image-0-alt', 'alt text');
    $this->getSession()->getPage()->pressButton(t('Save and publish'));
    $this->assertSession()->statusCodeEquals(200);

    // Stash the media's url.
    $url = $this->getUrl();

    // Edit the media, not touching the file this time.
    $values = [
      'name[0][value]' => 'Test Media Changed',
    ];
    $this->postEntityEditForm($url, $values, 'Save and keep published');

    // Confirm Hello World! is not printed to the screen.
    $this->assertSession()->pageTextNotContains("Hello World!");
  }

}
