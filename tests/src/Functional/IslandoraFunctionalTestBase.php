<?php

namespace Drupal\Tests\islandora\Functional;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Base class for Functional tests.
 */
class IslandoraFunctionalTestBase extends BrowserTestBase {

  use EntityReferenceTestTrait;
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

    // Delete the node rest config that's bootstrapped with Drupal.
    $this->container->get('entity_type.manager')->getStorage('rest_resource_config')->load('entity.node')->delete();

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

    // Make some bundles and field by hand so hooks fire.
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

    // Create a test content type.
    $test_type = $this->container->get('entity_type.manager')->getStorage('node_type')->create([
      'type' => 'test_type',
      'name' => 'Test Type',
    ]);
    $test_type->save();

    // Create a test content type.
    $test_type_with_reference = $this->container->get('entity_type.manager')->getStorage('node_type')->create([
      'type' => 'test_type_with_reference',
      'name' => 'Test Type With Reference',
    ]);
    $test_type_with_reference->save();

    // Add two entity reference fields.
    // One for nodes and one for media.
    $this->createEntityReferenceField('node', 'test_type_with_reference', 'field_node', 'Referenced Node', 'node', 'default', [], 2);
    $this->createEntityReferenceField('node', 'test_type_with_reference', 'field_media', 'Referenced Media', 'media', 'default', [], 2);

    // Copy over the rest of the config from yml files.
    $source = new FileStorage(__DIR__ . '/../../fixtures/config');
    $destination = $this->container->get('config.storage');

    foreach ($source->listAll() as $name) {
      $destination->write($name, $source->read($name));
    }

    // Cache clear / rebuild.
    drupal_flush_all_caches();
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
