<?php

namespace Drupal\Tests\islandora\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Abstract base class for Islandora kernel tests.
 *
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
    'rdf',
    'jsonld',
    'islandora'
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Bootstrap minimal Drupal environment to run the tests.
    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('user');
    $this->installConfig('filter');
    $this->installEntitySchema('fedora_resource');
  }

}

