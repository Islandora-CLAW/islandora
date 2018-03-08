<?php

namespace Drupal\Tests\islandora\Functional;

/**
 * Class MappingUriPredicateReactionTest.
 *
 * @package Drupal\Tests\islandora\Functional
 * @group islandora
 */
class MappingUriPredicateReactionTest extends IslandoraFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $types = ['schema:Thing'];
    $created_mapping = [
      'properties' => ['schema:dateCreated'],
      'datatype' => 'xsd:dateTime',
      'datatype_callback' => ['callable' => 'Drupal\rdf\CommonDataConverter::dateIso8601Value'],
    ];

    // Save bundle mapping config.
    $this->rdfMapping = rdf_get_mapping('node', 'test_type')
      ->setBundleMapping(['types' => $types])
      ->setFieldMapping('created', $created_mapping)
      ->setFieldMapping('title', [
        'properties' => ['dc:title'],
        'datatype' => 'xsd:string',
      ])
      ->save();

    $resourceConfigStorage = $this->container
      ->get('entity_type.manager')
      ->getStorage('rest_resource_config');
    // There is already a setting for entity.node, so delete it.
    $resourceConfigStorage
      ->delete($resourceConfigStorage
        ->loadMultiple(['entity.node']));
    // Create it new.
    $resourceConfigStorage->create([
      'id' => 'entity.node',
      'granularity' => 'resource',
      'configuration' => [
        'methods' => ['GET'],
        'authentication' => ['basic_auth', 'cookie'],
        'formats' => ['jsonld'],
      ],
      'status' => TRUE,
    ])->save(TRUE);

    $this->container->get('router.builder')->rebuildIfNeeded();
  }

  /**
   * @covers \Drupal\islandora\Plugin\ContextReaction\MappingUriPredicateReaction
   */
  public function testMappingReaction() {
    $account = $this->drupalCreateUser([
      'bypass node access',
      'administer contexts',
    ]);
    $this->drupalLogin($account);

    $context_name = 'test';
    $reaction_id = 'islandora_map_uri_predicate';

    $this->postNodeAddForm('test_type',
      ['title[0][value]' => 'Test Node'],
      t('Save'));
    $this->assertSession()->pageTextContains("Test Node");
    $url = $this->getUrl();

    // Make sure the node exists.
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);

    $contents = $this->drupalGet($url . '?_format=jsonld');
    $this->assertSession()->statusCodeEquals(200);
    $json = \GuzzleHttp\json_decode($contents, TRUE);
    $this->assertArrayHasKey('http://purl.org/dc/terms/title',
      $json['@graph'][0], 'Missing dcterms:title key');
    $this->assertEquals(
      'Test Node',
      $json['@graph'][0]['http://purl.org/dc/terms/title'][0]['@value'],
      'Missing title value'
    );
    $this->assertArrayNotHasKey('http://www.w3.org/2002/07/owl#sameAs',
      $json['@graph'][0], 'Has predicate when not configured');

    $this->createContext('Test', $context_name);
    $this->drupalGet("admin/structure/context/$context_name/reaction/add/$reaction_id");
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet("admin/structure/context/$context_name");
    // Can't use an undefined prefix.
    $this->getSession()->getPage()
      ->fillField("Drupal URI predicate", "bob:smith");
    $this->getSession()->getPage()->pressButton("Save and continue");
    $this->assertSession()
      ->pageTextContains("Namespace prefix bob is not registered");

    // Can't use a straight string.
    $this->getSession()->getPage()
      ->fillField("Drupal URI predicate", "woohoo");
    $this->getSession()->getPage()->pressButton("Save and continue");
    $this->assertSession()
      ->pageTextContains("Predicate must use a defined prefix or be a full URI");

    // Use an existing prefix.
    $this->getSession()->getPage()
      ->fillField("Drupal URI predicate", "owl:sameAs");
    $this->getSession()->getPage()->pressButton("Save and continue");
    $this->assertSession()
      ->pageTextContains("The context $context_name has been saved");

    $new_contents = $this->drupalGet($url . '?_format=jsonld');
    $json = \GuzzleHttp\json_decode($new_contents, TRUE);
    $this->assertEquals(
      'Test Node',
      $json['@graph'][0]['http://purl.org/dc/terms/title'][0]['@value'],
      'Missing title value'
    );
    $this->assertEquals(
      "$url?_format=jsonld",
      $json['@graph'][0]['http://www.w3.org/2002/07/owl#sameAs'][0]['@value'],
      'Missing alter added predicate.'
    );

    $this->drupalGet("admin/structure/context/$context_name");
    // Change to a random URL.
    $this->getSession()->getPage()
      ->fillField("Drupal URI predicate", "http://example.org/first/second");
    $this->getSession()->getPage()->pressButton("Save and continue");
    $this->assertSession()
      ->pageTextContains("The context $context_name has been saved");
    $new_contents = $this->drupalGet($url . '?_format=jsonld');
    $json = \GuzzleHttp\json_decode($new_contents, TRUE);
    $this->assertEquals(
      'Test Node',
      $json['@graph'][0]['http://purl.org/dc/terms/title'][0]['@value'],
      'Missing title value'
    );
    $this->assertArrayNotHasKey('http://www.w3.org/2002/07/owl#sameAs',
      $json['@graph'][0], 'Still has old predicate');
    $this->assertEquals(
      "$url?_format=jsonld",
      $json['@graph'][0]['http://example.org/first/second'][0]['@value'],
      'Missing alter added predicate.'
    );
  }

}
