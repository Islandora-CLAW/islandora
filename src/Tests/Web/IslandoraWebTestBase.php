<?php

namespace Drupal\islandora\Tests\Web;

use Drupal\simpletest\WebTestBase;

/**
 * Abstract base class for Islandora Web tests.
 */
abstract class IslandoraWebTestBase extends WebTestBase {

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
    'typed_data',
    'rules',
    'jsonld',
    'views',
    'key',
    'jwt',
    'islandora',
  ];

}
