<?php

namespace Drupal\Tests\islandora\Functional;

/**
 * Class JsonldTypeAlterReactionTest.
 *
 * @package Drupal\Tests\islandora\Functional
 * @group islandora
 */
class JsonldTypeAlterReactionTest extends MappingUriPredicateReactionTest {

  /**
   * @covers \Drupal\islandora\Plugin\ContextReaction\JsonldTypeAlterReaction
   */
  public function testMappingReaction() {
    $account = $this->drupalCreateUser([
      'bypass node access',
      'administer contexts',
      'administer node fields',
    ]);
    $this->drupalLogin($account);

    // add the typed predicate we will select in the reaction config.
    // DEBUG: it is crashing on FieldUiTestTrait.php:45 and I can't make it work.
    $this->fieldUIAddNewField('admin/structure/types/manage/test_type', 'field_type_predicate', 'Type Predicate', 'string');

    $context_name = 'test';
    $reaction_id = 'alter_jsonld_type';
    $this->postNodeAddForm('test_type', [
      'title[0][value]' => 'Test Node',
      'field_type_predicate[0][value]' => 'org:Organization',
    ],t('Save'));
    $this->assertSession()->pageTextContains("Test Node");
    $url = $this->getUrl();

    // Make sure the node exists.
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);

    $contents = $this->drupalGet($url . '?_format=jsonld');
    $this->assertSession()->statusCodeEquals(200);
    $json = \GuzzleHttp\json_decode($contents, TRUE);
    $this->assertArrayHasKey('@type',
      $json['@graph'][0], 'Missing @type');
    $this->assertEquals(
      'http://schema.org/Thing',
      $json['@graph'][0]['@type'][0],
      'Missing @type value of http://schema.org/Thing'
    );

    $this->createContext('Test', $context_name);
    $this->drupalGet("admin/structure/context/$context_name/reaction/add/$reaction_id");
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet("admin/structure/context/$context_name");
    $this->getSession()->getPage()
      ->fillField("Source Field", "field_type_predicate");
    $this->getSession()->getPage()->pressButton("Save and continue");
    $this->assertSession()
      ->pageTextContains("The context $context_name has been saved");

    $this->addCondition('test', 'entity_bundle');
    $this->getSession()->getPage()->checkField("edit-conditions-entity-bundle-bundles-test-type");
    $this->getSession()->getPage()->findById("edit-conditions-entity-bundle-context-mapping-node")->selectOption("@node.node_route_context:node");
    $this->getSession()->getPage()->pressButton(t('Save and continue'));

    // $new_contents = $this->drupalGet($url . '?_format=jsonld');
    // $json = \GuzzleHttp\json_decode($new_contents, TRUE);
    // $this->assertEquals(
    //   'Test Node',
    //   $json['@graph'][0]['http://purl.org/dc/terms/title'][0]['@value'],
    //   'Missing title value'
    // );
    // $this->assertEquals(
    //   "$url?_format=jsonld",
    //   $json['@graph'][0]['http://www.w3.org/2002/07/owl#sameAs'][0]['@value'],
    //   'Missing alter added predicate.'
    // );
    //
    // $this->drupalGet("admin/structure/context/$context_name");
    // // Change to a random URL.
    // $this->getSession()->getPage()
    //   ->fillField("Drupal URI predicate", "http://example.org/first/second");
    // $this->getSession()->getPage()->pressButton("Save and continue");
    // $this->assertSession()
    //   ->pageTextContains("The context $context_name has been saved");
    // $new_contents = $this->drupalGet($url . '?_format=jsonld');
    // $json = \GuzzleHttp\json_decode($new_contents, TRUE);
    // $this->assertEquals(
    //   'Test Node',
    //   $json['@graph'][0]['http://purl.org/dc/terms/title'][0]['@value'],
    //   'Missing title value'
    // );
    // $this->assertArrayNotHasKey('http://www.w3.org/2002/07/owl#sameAs',
    //   $json['@graph'][0], 'Still has old predicate');
    // $this->assertEquals(
    //   "$url?_format=jsonld",
    //   $json['@graph'][0]['http://example.org/first/second'][0]['@value'],
    //   'Missing alter added predicate.'
    // );
  }

}
