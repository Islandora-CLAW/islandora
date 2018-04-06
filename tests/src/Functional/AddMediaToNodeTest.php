<?php

namespace Drupal\Tests\islandora\Functional;

use Drupal\Core\Url;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;

/**
 * Tests the RelatedLinkHeader view alter.
 *
 * @group islandora
 */
class AddMediaToNodeTest extends IslandoraFunctionalTestBase {

  use EntityReferenceTestTrait;

  /**
   * Node that has entity reference field.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $referencer;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->referencer = $this->container->get('entity_type.manager')->getStorage('node')->create([
      'type' => 'test_type_with_reference',
      'title' => 'Referencer',
    ]);
    $this->referencer->save();
  }

  /**
   * @covers \Drupal\islandora\Controller\MediaSourceController::addToNode
   */
  public function testAddMediaToNode() {
    // Hack out the guzzle client.
    $client = $this->getSession()->getDriver()->getClient()->getClient();

    $add_to_node_url = Url::fromRoute(
      'islandora.media_source_add_to_node',
      [
        'node' => $this->referencer->id(),
        'field' => 'field_media',
        'bundle' => 'tn',
      ]
    )
      ->setAbsolute()
      ->toString();

    $bad_node_url = Url::fromRoute(
      'islandora.media_source_add_to_node',
      [
        'node' => 123456,
        'field' => 'field_media',
        'bundle' => 'tn',
      ]
    )
      ->setAbsolute()
      ->toString();

    $image = file_get_contents(__DIR__ . '/../../fixtures/test.jpeg');

    // Test different permissions scenarios.
    $options = [
      'http_errors' => FALSE,
      'headers' => [
        'Content-Type' => 'image/jpeg',
        'Content-Disposition' => 'attachment; filename="test.jpeg"',
      ],
      'body' => $image,
    ];

    // 403 if you don't have permissions to update the node.
    $account = $this->drupalCreateUser([
      'access content',
      'create media',
    ]);
    $this->drupalLogin($account);
    $options['auth'] = [$account->getUsername(), $account->pass_raw];
    $response = $client->request('POST', $add_to_node_url, $options);
    $this->assertTrue($response->getStatusCode() == 403, "Expected 403, received {$response->getStatusCode()}");

    // Bad node URL should return 404, regardless of permissions.
    // Just making sure our custom access function doesn't obfuscate responses.
    $response = $client->request('POST', $bad_node_url, $options);
    $this->assertTrue($response->getStatusCode() == 404, "Expected 404, received {$response->getStatusCode()}");

    // 403 if you don't have permissions to create Media.
    $account = $this->drupalCreateUser([
      'bypass node access',
    ]);
    $this->drupalLogin($account);
    $options['auth'] = [$account->getUsername(), $account->pass_raw];
    $response = $client->request('POST', $add_to_node_url, $options);
    $this->assertTrue($response->getStatusCode() == 403, "Expected 403, received {$response->getStatusCode()}");

    // Now with proper credentials, test responses given to malformed requests.
    $account = $this->drupalCreateUser([
      'bypass node access',
      'create media',
    ]);
    $this->drupalLogin($account);

    // Request without Content-Type header should fail with 400.
    $options = [
      'auth' => [$account->getUsername(), $account->pass_raw],
      'http_errors' => FALSE,
      'headers' => [
        'Content-Disposition' => 'attachment; filename="test.jpeg"',
      ],
      'body' => $image,
    ];
    $response = $client->request('POST', $add_to_node_url, $options);
    $this->assertTrue($response->getStatusCode() == 400, "Expected 400, received {$response->getStatusCode()}");

    // Request without Content-Disposition header should fail with 400.
    $options = [
      'auth' => [$account->getUsername(), $account->pass_raw],
      'http_errors' => FALSE,
      'headers' => [
        'Content-Type' => 'image/jpeg',
      ],
      'body' => $image,
    ];
    $response = $client->request('POST', $add_to_node_url, $options);
    $this->assertTrue($response->getStatusCode() == 400, "Expected 400, received {$response->getStatusCode()}");

    // Request with malformed Content-Disposition header should fail with 400.
    $options = [
      'auth' => [$account->getUsername(), $account->pass_raw],
      'http_errors' => FALSE,
      'headers' => [
        'Content-Type' => 'image/jpeg',
        'Content-Disposition' => 'garbage; filename="test.jpeg"',
      ],
      'body' => $image,
    ];
    $response = $client->request('POST', $add_to_node_url, $options);
    $this->assertTrue($response->getStatusCode() == 400, "Expected 400, received {$response->getStatusCode()}");

    // Request without body should fail with 400.
    $options = [
      'auth' => [$account->getUsername(), $account->pass_raw],
      'http_errors' => FALSE,
      'headers' => [
        'Content-Type' => 'image/jpeg',
        'Content-Disposition' => 'attachment; filename="test.jpeg"',
      ],
    ];
    $response = $client->request('POST', $add_to_node_url, $options);
    $this->assertTrue($response->getStatusCode() == 400, "Expected 400, received {$response->getStatusCode()}");

    // Test properly formed requests with bad parameters in the route.
    $options = [
      'auth' => [$account->getUsername(), $account->pass_raw],
      'http_errors' => FALSE,
      'headers' => [
        'Content-Type' => 'image/jpeg',
        'Content-Disposition' => 'attachment; filename="test.jpeg"',
      ],
      'body' => $image,
    ];

    // Bad node id should return 404 even with proper permissions.
    $response = $client->request('POST', $bad_node_url, $options);
    $this->assertTrue($response->getStatusCode() == 404, "Expected 404, received {$response->getStatusCode()}");

    // Bad field name in url should return 404.
    $bad_field_url = Url::fromRoute(
      'islandora.media_source_add_to_node',
      [
        'node' => $this->referencer->id(),
        'field' => 'field_garbage',
        'bundle' => 'tn',
      ]
    )
      ->setAbsolute()
      ->toString();
    $response = $client->request('POST', $bad_field_url, $options);
    $this->assertTrue($response->getStatusCode() == 404, "Expected 404, received {$response->getStatusCode()}");

    // Bad bundle name in url should return 404.
    $bad_bundle_url = Url::fromRoute(
      'islandora.media_source_add_to_node',
      [
        'node' => $this->referencer->id(),
        'field' => 'field_media',
        'bundle' => 'garbage',
      ]
    )
      ->setAbsolute()
      ->toString();
    $response = $client->request('POST', $bad_bundle_url, $options);
    $this->assertTrue($response->getStatusCode() == 404, "Expected 404, received {$response->getStatusCode()}");

    // Should be successful with proper url, options, and permissions.
    $options = [
      'auth' => [$account->getUsername(), $account->pass_raw],
      'http_errors' => FALSE,
      'headers' => [
        'Content-Type' => 'image/jpeg',
        'Content-Disposition' => 'attachment; filename="test.jpeg"',
      ],
      'body' => $image,
    ];
    $response = $client->request('POST', $add_to_node_url, $options);
    $this->assertTrue($response->getStatusCode() == 201, "Expected 201, received {$response->getStatusCode()}");
    $this->assertTrue(!empty($response->getHeader("Location")), "Response must include Location header");

    // Should fail with 409 if Node already references a media using the field
    // (i.e. the previous call was successful).
    $response = $client->request('POST', $add_to_node_url, $options);
    $this->assertTrue($response->getStatusCode() == 409, "Expected 409, received {$response->getStatusCode()}");
  }

}
