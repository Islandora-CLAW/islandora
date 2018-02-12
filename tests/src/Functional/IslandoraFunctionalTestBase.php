<?php

namespace Drupal\Tests\islandora\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Base class for Functional tests.
 */
class IslandoraFunctionalTestBase extends BrowserTestBase {

  use TestFileCreationTrait;

  protected static $modules = ['context_ui', 'islandora'];

  protected static $configSchemaCheckerExclusions = [
    'jwt.config',
    'context.context.test',
    'context.context.node',
    'context.context.media',
    'context.context.file',
    'key.key.test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Delete the context entities provided by the module.
    // This will get removed as we split apart contexts into different
    // solution packs.
    $this->container->get('entity_type.manager')->getStorage('context')->load('node')->delete();
    $this->container->get('entity_type.manager')->getStorage('context')->load('media')->delete();
    $this->container->get('entity_type.manager')->getStorage('context')->load('file')->delete();

    // Create a test content type.
    $test_type = $this->container->get('entity_type.manager')->getStorage('node_type')->create([
      'type' => 'test_type',
      'label' => 'Test Type',
    ]);
    $test_type->save();

    // Create an action that dsm's "Hello World!".
    $hello_world = $this->container->get('entity_type.manager')->getStorage('action')->create([
      'id' => 'hello_world',
      'label' => 'Hello World',
      'type' => 'system',
      'plugin' => 'action_message_action',
      'configuration' => [
        'message' => 'Hello World!',
      ],
    ]);
    $hello_world->save();

    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Creates a test context.
   */
  protected function createContext($label, $name) {
    $this->drupalPostForm('admin/structure/context/add', ['label' => $label, 'name' => $name], t('Save'));
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Adds a condition to the test context.
   */
  protected function addCondition($context_id, $condition_id) {
    $this->drupalGet("admin/structure/context/$context_id/condition/add/$condition_id");
    $this->getSession()->getPage()->pressButton('Save and continue');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Adds a reaction to the test context.
   */
  protected function addPresetReaction($context_id, $reaction_type, $action_id) {
    $this->drupalGet("admin/structure/context/$context_id/reaction/add/$reaction_type");
    $this->getSession()->getPage()->findById("edit-reactions-$reaction_type-actions")->selectOption($action_id);
    $this->getSession()->getPage()->pressButton(t('Save and continue'));
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Creates a new TN media with a file.
   */
  protected function createThumbnailWithFile() {
    // Have to do this in two steps since there's no ajax for the alt text.
    // It's as annoying as in real life.
    $file = current($this->getTestFiles('image'));
    $values = [
      'name[0][value]' => 'Test Media',
      'files[field_image_0]' => \Drupal::service('file_system')->realpath($file->uri),
    ];
    $this->drupalPostForm('media/add/tn', $values, t('Save and publish'));
    $values = [
      'field_image[0][alt]' => 'Alternate text',
    ];
    $this->getSession()->getPage()->fillField('edit-field-image-0-alt', 'alt text');
    $this->getSession()->getPage()->pressButton(t('Save and publish'));
    $this->assertSession()->statusCodeEquals(200);
    $results = $this->container->get('entity_type.manager')->getStorage('file')->loadByProperties(['filename' => $file->filename]);
    $file_entity = reset($results);
    $file_url = $file_entity->url('canonical', ['absolute' => TRUE]);
    $rest_url = Url::fromRoute('islandora.media_source_update', ['media' => $file_entity->id()])
      ->setAbsolute()
      ->toString();
    $rest_url .= "?_format=json";
    return [
      'media' => $this->getUrl(),
      'file' => [
        'file' => $file_url,
        'rest' => $rest_url,
      ],
    ];
  }

  /**
   * Create a new node by posting its add form.
   */
  protected function postNodeAddForm($bundle_id, $values, $button_text) {
    $this->drupalPostForm("node/add/$bundle_id", $values, t('@text', ['@text' => $button_text]));
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Edits a node by posting its edit form.
   */
  protected function postEntityEditForm($entity_url, $values, $button_text) {
    $this->drupalPostForm("$entity_url/edit", $values, t('@text', ['@text' => $button_text]));
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Utility function to check if a link header is included in the response.
   *
   * @param string $rel
   *   The relation to search for.
   *
   * @return bool
   *   TRUE if link header with relation is included in the response.
   */
  protected function doesNotHaveLinkHeader($rel) {
    $headers = $this->getSession()->getResponseHeaders();

    foreach ($headers['Link'] as $link_header) {
      if (strpos($link_header, "rel=\"$rel\"") !== FALSE) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Checks if the correct link header exists for an Entity.
   *
   * @param string $rel
   *   The expected relation type of the link header.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose uri is expected in the link header.
   * @param string $title
   *   The expected title of the link header.
   * @param string $type
   *   The expected mimetype for the link header.
   *
   * @return int
   *   The number of times the correct header appears.
   */
  protected function validateLinkHeaderWithEntity($rel, EntityInterface $entity, $title = '', $type = '') {
    $entity_url = $entity->toUrl('canonical', ['absolute' => TRUE])
      ->toString();
    return $this->validateLinkHeaderWithUrl($rel, $entity_url, $title, $type);
  }

  /**
   * Checks if the correct link header exists for a string URI.
   *
   * @param string $rel
   *   The expected relation type of the link header.
   * @param string $url
   *   The uri is expected in the link header.
   * @param string $title
   *   The expected title of the link header.
   * @param string $type
   *   The expected mimetype for the link header.
   *
   * @return int
   *   The number of times the correct header appears.
   */
  protected function validateLinkHeaderWithUrl($rel, $url, $title = '', $type = '') {

    $regex = '/<(.*)>; rel="' . preg_quote($rel) . '"';
    if (!empty($title)) {
      $regex .= '; title="' . preg_quote($title) . '"';
    }
    if (!empty($type)) {
      $regex .= '; type="' . preg_quote($type, '/') . '"';
    }
    $regex .= '/';

    $count = 0;

    $headers = $this->getSession()->getResponseHeaders();

    foreach ($headers['Link'] as $link_headers) {
      $split = explode(',', $link_headers);
      foreach ($split as $link_header) {
        $matches = [];
        if (preg_match($regex, $link_header, $matches) && $matches[1] == $url) {
          $count++;
        }
      }
    }

    return $count;
  }

}
