<?php

namespace Drupal\Tests\islandora\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Abstract base class for Islandora kernel tests.
 */
abstract class IslandoraKernelTestBase extends KernelTestBase {

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
    'basic_auth',
    'hal',
    'rdf',
    'action',
    'context',
    'jsonld',
    'views',
    'key',
    'jwt',
    'file',
    'entity',
    'image',
    'media_entity',
    'media_entity_image',
    'islandora',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Bootstrap minimal Drupal environment to run the tests.
    $this->installSchema('system', 'sequences');
    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('context');
    $this->installConfig('filter');
  }

}
