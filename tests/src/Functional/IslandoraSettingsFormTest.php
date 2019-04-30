<?php

namespace Drupal\Tests\islandora\Functional;

/**
 * Class IslandoraSettingsFormTest.
 *
 * @package Drupal\Tests\islandora\Functional
 * @group islandora
 * @coversDefaultClass \Drupal\islandora\Form\IslandoraSettingsForm
 */
class IslandoraSettingsFormTest extends IslandoraFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a test user.
    $account = $this->drupalCreateUser([
      'bypass node access',
      'administer site configuration',
      'view media',
      'create media',
      'update media',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Test Gemini URL validation.
   */
  public function testGeminiUri() {
    $this->drupalGet('/admin/config/islandora/core');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("Gemini URL");
    $this->assertSession()->fieldValueEquals('edit-gemini-url', '');

    $this->drupalPostForm('admin/config/islandora/core', ['edit-gemini-url' => 'not_a_url'], t('Save configuration'));
    $this->assertSession()->pageTextContainsOnce("Cannot parse URL not_a_url");

    $this->drupalPostForm('admin/config/islandora/core', ['edit-gemini-url' => 'http://whaturl.bob'], t('Save configuration'));
    $this->assertSession()->pageTextContainsOnce("Cannot connect to URL http://whaturl.bob");
  }

  /**
   * Test block on choosing Pseudo field bundles without a Gemini URL.
   */
  public function testPseudoFieldBundles() {
    $this->drupalGet('/admin/config/islandora/core');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalPostForm('admin/config/islandora/core', [
      'gemini_pseudo_bundles[test_type:node]' => TRUE,
    ], t('Save configuration'));
    $this->assertSession()->pageTextContainsOnce("Must enter Gemini URL before selecting bundles to display a pseudo field on.");

  }

}
