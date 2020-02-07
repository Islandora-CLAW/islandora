<?php

namespace Drupal\islandora;

use Drupal\Core\Entity\EntityInterface;
use Drupal\islandora\MediaSource\MediaSourceService;
use Drupal\jwt\Authentication\Provider\JwtAuth;
use GuzzleHttp\Psr7;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Islandora\Crayfish\Commons\Client\GeminiClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Locates the matching Fedora URI from the Gemini database.
 *
 * @package Drupal\islandora
 */
class GeminiLookup {

  /**
   * A GeminiClient.
   *
   * @var \Islandora\Crayfish\Commons\Client\GeminiClient
   */
  private $geminiClient;

  /**
   * A JWT Provider service.
   *
   * @var \Drupal\jwt\Authentication\Provider\JwtAuth
   */
  private $jwtProvider;

  /**
   * A MediaSourceService.
   *
   * @var \Drupal\islandora\MediaSource\MediaSourceService
   */
  private $mediaSource;

  /**
   * An http client.
   *
   * @var \GuzzleHttp\Client
   */
  private $guzzle;

  /**
   * The islandora logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * GeminiField constructor.
   *
   * @param \Islandora\Crayfish\Commons\Client\GeminiClient $client
   *   The Gemini client.
   * @param \Drupal\jwt\Authentication\Provider\JwtAuth $jwt_auth
   *   The JWT provider.
   * @param \Drupal\islandora\MediaSource\MediaSourceService $media_source
   *   Media source service.
   * @param \GuzzleHttp\Client $guzzle
   *   Guzzle client.
   * @param \Psr\Log\LoggerInterface $logger
   *   The Islandora logger.
   */
  public function __construct(
    GeminiClient $client,
    JwtAuth $jwt_auth,
    MediaSourceService $media_source,
    Client $guzzle,
    LoggerInterface $logger
  ) {
    $this->geminiClient = $client;
    $this->jwtProvider = $jwt_auth;
    $this->mediaSource = $media_source;
    $this->guzzle = $guzzle;
    $this->logger = $logger;
  }

  /**
   * Lookup this entity's URI in the Gemini db and return the other URI.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to look for.
   *
   * @return string|null
   *   Return the URI or null
   */
  public function lookup(EntityInterface $entity) {
    // Exit early if the entity hasn't been saved yet.
    if ($entity->id() == NULL) {
      return NULL;
    }

    $is_media = $entity->getEntityTypeId() == 'media';

    // Use the entity's uuid unless it's a media,
    // use its file's uuid instead.
    if ($is_media) {
      try {
        $file = $this->mediaSource->getSourceFile($entity);
        $uuid = $file->uuid();
      }
      // If the media has no source file, exit early.
      catch (NotFoundHttpException $e) {
        return NULL;
      }
    }
    else {
      $uuid = $entity->uuid();
    }

    // Look it up in Gemini.
    $token = "Bearer " . $this->jwtProvider->generateToken();
    $urls = $this->geminiClient->getUrls($uuid, $token);

    // Exit early if there's no results from Gemini.
    if (empty($urls)) {
      return NULL;
    }

    // If it's not a media, just return the url from Gemini;.
    if (!$is_media) {
      return $urls['fedora'];
    }

    // If it's a media, perform a HEAD request against
    // the file in Fedora and get its 'describedy' link header.
    try {
      $head = $this->guzzle->head(
        $urls['fedora'],
        ['allow_redirects' => FALSE, 'headers' => ['Authorization' => $token]]
      );
      $links = Psr7\parse_header($head->getHeader("Link"));
      foreach ($links as $link) {
        if ($link['rel'] == 'describedby') {
          return trim($link[0], '<>');
        }
      }
    }
    catch (RequestException $e) {
      $this->logger->warning(
        "Error performing Gemini lookup for media. Fedora HEAD to @url returned @status => @message",
        [
          '@url' => $urls['fedora'],
          '@status' => $e->getCode(),
          '@message' => $e->getMessage,
        ]
      );
      return NULL;
    }

    // Return null if no link header is found.
    return NULL;
  }

}
