<?php

namespace Drupal\Tests\islandora\Kernel;

use Drupal\islandora\JsonldContextGenerator\JsonldContextGenerator;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Json-LD context Generator methods and simple integration.
 *
 * @group islandora
 * @coversDefaultClass \Drupal\islandora\JsonldContextGenerator\JsonldContextGenerator
 */
class JsonldContextGeneratorTest extends KernelTestBase {

  use FedoraContentTypeCreationTrait {
    createFedoraResourceContentType as drupalCreateFedoraContentType;
  }
  public static $modules = [
    'system',
    'rdf',
    'islandora',
    'entity_test',
    'rdf_test_namespaces',
  ];


  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityBundleListenerInterface
   */
  protected $entityBundleListener;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The JsonldContextGenerator we are testing.
   *
   * @var \Drupal\islandora\JsonldContextGenerator\JsonldContextGeneratorInterface
   */
  protected $theJsonldContextGenerator;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $types = ['schema:Thing'];
    $mapping = [
      'properties' => ['schema:dateCreated'],
      'datatype' => 'xsd:dateTime',
      'datatype_callback' => ['callable' => 'Drupal\rdf\CommonDataConverter::dateIso8601Value'],
    ];

    // Save bundle mapping config.
    $rdfMapping = rdf_get_mapping('entity_test', 'rdf_source')
      ->setBundleMapping(['types' => $types])
      ->setFieldMapping('created', $mapping)
      ->save();
    // Initialize our generator.
    $this->theJsonldContextGenerator = new JsonldContextGenerator(
        $this->container->get('entity_field.manager'),
        $this->container->get('entity_type.bundle.info'),
        $this->container->get('entity_type.manager'),
        $this->container->get('cache.default'),
        $this->container->get('logger.channel.islandora')
      );

  }

  /**
   * @covers \Drupal\islandora\JsonldContextGenerator\JsonldContextGenerator::getContext
   */
  public function testGetContext() {
    // Test with known asserts.
    $context = $this->theJsonldContextGenerator->getContext('entity_test.rdf_source');
    $context_as_array = json_decode($context, TRUE);
    $this->assertTrue(is_array($context_as_array), 'JSON-LD Context generated has correct structure for known Bundle');

    $this->assertTrue(strpos($context, '"schema": "http://schema.org/"') !== FALSE, "JSON-LD Context generated contains the expected values for known Bundle");

  }

  /**
   * Tests Exception in case of no rdf type.
   *
   * @expectedException \Exception
   * @covers \Drupal\islandora\JsonldContextGenerator\JsonldContextGenerator::getContext
   */
  public function testGetContextException() {
    // This should throw the expected Exception.
    $newFedoraEntity = $this->drupalCreateFedoraContentType();
    $this->theJsonldContextGenerator->getContext('fedora_resource.' . $newFedoraEntity->id());

  }

  /**
   * @covers \Drupal\islandora\JsonldContextGenerator\JsonldContextGenerator::generateContext
   */
  public function testGenerateContext() {
    // Test with known asserts.
    $rdfMapping = rdf_get_mapping('entity_test', 'rdf_source');
    $context = $this->theJsonldContextGenerator->generateContext($rdfMapping);
    $context_as_array = json_decode($context, TRUE);
    $this->assertTrue(is_array($context_as_array), 'JSON-LD Context generated has correct structure for known Bundle');

    $this->assertTrue(strpos($context, '"schema": "http://schema.org/"') !== FALSE, "JSON-LD Context generated contains the expected values for known Bundle");

  }

  /**
   * Tests Exception in case of no rdf type.
   *
   * @expectedException \Exception
   * @covers \Drupal\islandora\JsonldContextGenerator\JsonldContextGenerator::generateContext
   */
  public function testGenerateContextException() {
    // This should throw the expected Exception.
    $newFedoraEntity = $this->drupalCreateFedoraContentType();
    $rdfMapping = rdf_get_mapping('fedora_resource', $newFedoraEntity->id());
    $this->theJsonldContextGenerator->getContext('fedora_resource.' . $newFedoraEntity->id());

  }

}
