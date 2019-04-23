<?php

namespace Drupal\islandora;

use Drupal\Core\Entity\EntityInterface;
use Drupal\jwt\Authentication\Provider\JwtAuth;
use Islandora\Crayfish\Commons\Client\GeminiClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * @param \Psr\Log\LoggerInterface $logger
   *   The Islandora logger.
   */
  public function __construct(GeminiClient $client, JwtAuth $jwt_auth, LoggerInterface $logger) {
    $this->geminiClient = $client;
    $this->jwtProvider = $jwt_auth;
    $this->logger = $logger;
  }

  /**
   * Static creator.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   *
   * @return \Drupal\islandora\GeminiLookup
   *   A GeminiLookup service.
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('islandora.gemini_client'),
        $container->get('jwt.authentication.jwt'),
        $container->get('logger.channel.islandora')
    );
  }

  /**
   * Lookup this entity's URI in the Gemini db and return the other URI.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to look for.
   *
   * @return string|null
   *   Return the URI or null
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   *   If the entity cannot be converted to a URL.
   */
  public function lookup(EntityInterface $entity) {
    if ($entity->id() != NULL) {
      $drupal_uri = $entity->toUrl()->setAbsolute()->toString();
      $drupal_uri .= '?_format=jsonld';
      $token = "Bearer " . $this->jwtProvider->generateToken();
      $linked_uri = $this->geminiClient->findByUri($drupal_uri, $token);
      if (!is_null($linked_uri)) {
        if (is_array($linked_uri)) {
          $linked_uri = reset($linked_uri);
        }
        return $linked_uri;
      }
    }
    // Return null if we weren't in a saved entity or we didn't find a uri.
    return NULL;
  }

}
