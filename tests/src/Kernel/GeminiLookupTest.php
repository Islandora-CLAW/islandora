<?php

namespace Drupal\Tests\islandora\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\islandora\GeminiLookup;
use Drupal\jwt\Authentication\Provider\JwtAuth;
use Islandora\Crayfish\Commons\Client\GeminiClient;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

/**
 * Class GeminiLookupTest.
 *
 * @group islandora
 * @coversDefaultClass \Drupal\islandora\GeminiLookup
 */
class GeminiLookupTest extends IslandoraKernelTestBase {

  private $geminiLookup;

  private $geminiClient;

  private $jwtAuth;

  private $logger;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $prophecy = $this->prophesize(JwtAuth::class);
    $prophecy->generateToken()->willReturn("islandora");
    $this->jwtAuth = $prophecy->reveal();

    $prophecy = $this->prophesize(LoggerInterface::class);
    $this->logger = $prophecy->reveal();

    $prophecy = $this->prophesize(GeminiClient::class);
    $prophecy->findByUri(Argument::any(), Argument::any())->willReturn(NULL);
    $this->geminiClient = $prophecy->reveal();
  }

  /**
   * @covers ::lookup
   * @covers ::__construct
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function testEntityNotSaved() {
    $prophecy = $this->prophesize(EntityInterface::class);
    $prophecy->id()->willReturn(NULL);
    $entity = $prophecy->reveal();
    $this->geminiLookup = new GeminiLookup(
        $this->geminiClient,
        $this->jwtAuth,
        $this->logger
    );
    $this->assertEquals(NULL, $this->geminiLookup->lookup($entity));
  }

  /**
   * @covers ::lookup
   * @covers ::__construct
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function testEntityNotFound() {
    $prop1 = $this->prophesize(Url::class);
    $prop1->toString()->willReturn("http://localhost:8000/node/456");

    $prop2 = $this->prophesize(Url::class);
    $prop2->setAbsolute()->willReturn($prop1->reveal());
    $url = $prop2->reveal();

    $prophecy = $this->prophesize(EntityInterface::class);
    $prophecy->id()->willReturn(456);
    $prophecy->toUrl()->willReturn($url);
    $entity = $prophecy->reveal();

    $this->geminiLookup = new GeminiLookup(
        $this->geminiClient,
        $this->jwtAuth,
        $this->logger
    );

    $this->assertEquals(NULL, $this->geminiLookup->lookup($entity));
  }

  /**
   * @covers ::lookup
   * @covers ::__construct
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function testEntityFound() {
    $prop1 = $this->prophesize(Url::class);
    $prop1->toString()->willReturn("http://localhost:8000/node/456");

    $prop2 = $this->prophesize(Url::class);
    $prop2->setAbsolute()->willReturn($prop1->reveal());
    $url = $prop2->reveal();

    $prophecy = $this->prophesize(EntityInterface::class);
    $prophecy->id()->willReturn(456);
    $prophecy->toUrl()->willReturn($url);
    $entity = $prophecy->reveal();

    $prophecy = $this->prophesize(GeminiClient::class);
    $prophecy->findByUri(Argument::any(), Argument::any())->willReturn(["http://fedora:8080/some/uri"]);
    $this->geminiClient = $prophecy->reveal();

    $this->geminiLookup = new GeminiLookup(
        $this->geminiClient,
        $this->jwtAuth,
        $this->logger
    );

    $this->assertEquals("http://fedora:8080/some/uri", $this->geminiLookup->lookup($entity));
  }

}
