<?php

namespace Drupal\Tests\islandora\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\islandora\GeminiLookup;
use Drupal\islandora\MediaSource\MediaSourceService;
use Drupal\jwt\Authentication\Provider\JwtAuth;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Islandora\Crayfish\Commons\Client\GeminiClient;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class GeminiLookupTest.
 *
 * @group islandora
 * @coversDefaultClass \Drupal\islandora\GeminiLookup
 */
class GeminiLookupTest extends IslandoraKernelTestBase {

  /**
   * JWT Auth.
   *
   * @var \Drupal\jwt\Authentication\Provider\JwtAuth
   */
  private $jwtAuth;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * Guzzle.
   *
   * @var \GuzzleHttp\Client
   */
  private $guzzle;

  /**
   * Gemini client.
   *
   * @var \Islandora\Crayfish\Commons\Client\GeminiClient
   */
  private $geminiClient;

  /**
   * Media source service.
   *
   * @var \Drupal\islandora\MediaSource\MediaSourceService
   */
  private $mediaSource;

  /**
   * An entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  private $entity;

  /**
   * A media.
   *
   * @var \Drupal\media\MediaInterface
   */
  private $media;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Mock up dummy objects by default.
    $prophecy = $this->prophesize(JwtAuth::class);
    $this->jwtAuth = $prophecy->reveal();

    $prophecy = $this->prophesize(LoggerInterface::class);
    $this->logger = $prophecy->reveal();

    $prophecy = $this->prophesize(MediaSourceService::class);
    $this->mediaSource = $prophecy->reveal();

    $prophecy = $this->prophesize(GeminiClient::class);
    $this->geminiClient = $prophecy->reveal();

    $prophecy = $this->prophesize(Client::class);
    $this->guzzle = $prophecy->reveal();

    // Mock up an entity to use (node in this case).
    $prophecy = $this->prophesize(EntityInterface::class);
    $prophecy->id()->willReturn(1);
    $prophecy->getEntityTypeId()->willReturn('node');
    $prophecy->uuid()->willReturn('abc123');
    $this->entity = $prophecy->reveal();

    // Mock up a media to use.
    $prophecy = $this->prophesize(MediaInterface::class);
    $prophecy->id()->willReturn(1);
    $prophecy->getEntityTypeId()->willReturn('media');
    $prophecy->uuid()->willReturn('abc123');
    $this->media = $prophecy->reveal();
  }

  /**
   * Mocks up a gemini client that fails its lookup.
   */
  private function mockGeminiClientForFail() {
    $prophecy = $this->prophesize(GeminiClient::class);
    $prophecy->getUrls(Argument::any(), Argument::any())
      ->willReturn([]);
    $this->geminiClient = $prophecy->reveal();
  }

  /**
   * Mocks up a gemini client that finds a fedora url.
   */
  private function mockGeminiClientForSuccess() {
    $prophecy = $this->prophesize(GeminiClient::class);
    $prophecy->getUrls(Argument::any(), Argument::any())
      ->willReturn(['drupal' => '', 'fedora' => 'http://localhost:8080/fcrepo/rest/abc123']);
    $this->geminiClient = $prophecy->reveal();
  }

  /**
   * Mocks up a media source service that finds the source file for a media.
   */
  private function mockMediaSourceForSuccess() {
    $prophecy = $this->prophesize(FileInterface::class);
    $prophecy->uuid()->willReturn('abc123');
    $file = $prophecy->reveal();

    $prophecy = $this->prophesize(MediaSourceService::class);
    $prophecy->getSourceFile(Argument::any())
      ->willReturn($file);
    $this->mediaSource = $prophecy->reveal();
  }

  /**
   * Make the gemini lookup out of class variables.
   */
  private function createGeminiLookup() {
    return new GeminiLookup(
      $this->geminiClient,
      $this->jwtAuth,
      $this->mediaSource,
      $this->guzzle,
      $this->logger
    );
  }

  /**
   * @covers ::lookup
   * @covers ::__construct
   */
  public function testEntityNotSaved() {
    // Mock an entity that returns a null id.
    // That means it's not saved in the db yet.
    $prophecy = $this->prophesize(EntityInterface::class);
    $prophecy->id()->willReturn(NULL);
    $this->entity = $prophecy->reveal();

    $gemini_lookup = $this->createGeminiLookup();

    $this->assertEquals(
      NULL,
      $gemini_lookup->lookup($this->entity)
    );
  }

  /**
   * @covers ::lookup
   * @covers ::__construct
   */
  public function testEntityNotFound() {
    $this->mockGeminiClientForFail();

    $gemini_lookup = $this->createGeminiLookup();

    $this->assertEquals(
      NULL,
      $gemini_lookup->lookup($this->entity)
    );
  }

  /**
   * @covers ::lookup
   * @covers ::__construct
   */
  public function testEntityFound() {
    $this->mockGeminiClientForSuccess();

    $gemini_lookup = $this->createGeminiLookup();

    $this->assertEquals(
      'http://localhost:8080/fcrepo/rest/abc123',
      $gemini_lookup->lookup($this->entity)
    );
  }

  /**
   * @covers ::lookup
   * @covers ::__construct
   */
  public function testMediaHasNoSourceFile() {
    // Mock a media source service that fails to find
    // the source file for a media.
    $prophecy = $this->prophesize(MediaSourceService::class);
    $prophecy->getSourceFile(Argument::any())
      ->willThrow(new NotFoundHttpException("Media has no source"));
    $this->mediaSource = $prophecy->reveal();

    $gemini_lookup = $this->createGeminiLookup();

    $this->assertEquals(
      NULL,
      $gemini_lookup->lookup($this->media)
    );
  }

  /**
   * @covers ::lookup
   * @covers ::__construct
   */
  public function testMediaNotFound() {
    $this->mockMediaSourceForSuccess();
    $this->mockGeminiClientForFail();

    $gemini_lookup = $this->createGeminiLookup();

    $this->assertEquals(
      NULL,
      $gemini_lookup->lookup($this->media)
    );
  }

  /**
   * @covers ::lookup
   * @covers ::__construct
   */
  public function testFileFoundButNoDescribedby() {
    $this->mockMediaSourceForSuccess();
    $this->mockGeminiClientForSuccess();

    // Mock up a guzzle client that does not return
    // the describedby header.
    $prophecy = $this->prophesize(Client::class);
    $prophecy->head(Argument::any(), Argument::any())
      ->willReturn(new Response(200, []));
    $this->guzzle = $prophecy->reveal();

    $gemini_lookup = $this->createGeminiLookup();

    $this->assertEquals(
      NULL,
      $gemini_lookup->lookup($this->media)
    );
  }

  /**
   * @covers ::lookup
   * @covers ::__construct
   */
  public function testMediaFound() {
    $this->mockMediaSourceForSuccess();
    $this->mockGeminiClientForSuccess();

    // Mock up a guzzle client that returns
    // the describedby header.
    $prophecy = $this->prophesize(Client::class);
    $prophecy->head(Argument::any(), Argument::any())
      ->willReturn(new Response(200, ['Link' => '<http://localhost:8080/fcrepo/rest/abc123/fcr:metadata>; rel="describedby"']));
    $this->guzzle = $prophecy->reveal();

    $gemini_lookup = $this->createGeminiLookup();

    $this->assertEquals(
      'http://localhost:8080/fcrepo/rest/abc123/fcr:metadata',
      $gemini_lookup->lookup($this->media)
    );
  }

}
