<?php

namespace Drupal\islandora\Flysystem;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\flysystem\Plugin\FlysystemPluginInterface;
use Drupal\flysystem\Plugin\FlysystemUrlTrait;
use Drupal\islandora\Flysystem\Adapter\FedoraAdapter;
use Drupal\jwt\Authentication\Provider\JwtAuth;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

/**
 * Drupal plugin for the Fedora Flysystem adapter.
 *
 * @Adapter(id = "fedora")
 */
class Fedora implements FlysystemPluginInterface, ContainerFactoryPluginInterface {

  use FlysystemUrlTrait;

  /**
   * JWT Authentication.
   *
   * @var \Drupal\jwt\Authentication\Provider\JwtAuth
   */
  protected $jwt;
  protected $configuration;

  protected $mimeTypeGuesser;

  /**
   * Constructs a Fedora plugin for Flysystem.
   *
   * @param \Drupal\jwt\Authentication\Provider\JwtAuth $jwt
   *   JWT Auth.
   * @param array $configuration
   *   Configuration.
   * @param \Symfony\Component\HttpFoundation\File\Mimetype\MimeTypeGuesserInterface $mime_type_guesser
   *   Mimetype guesser.
   */
  public function __construct(
    JwtAuth $jwt,
    array $configuration,
    MimeTypeGuesserInterface $mime_type_guesser
  ) {
    $this->jwt = $jwt;
    $this->configuration = $configuration;
    $this->mimeTypeGuesser = $mime_type_guesser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Construct Authorization header using jwt token.
    $jwt = $container->get('jwt.authentication.jwt');

    return new static(
      $jwt,
      $configuration,
      $container->get('file.mime_type.guesser')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getAdapter() {
    return new FedoraAdapter($this->jwt, $this->configuration, $this->mimeTypeGuesser);
  }

  /**
   * {@inheritdoc}
   */
  public function ensure($force = FALSE) {
    // Check fedora root for sanity.
    if (!$this->getAdapter()->has('')) {
      return [[
        'severity' => RfcLogLevel::ERROR,
        'message' => '%url returned %status',
        'context' => [
          '%url' => $this->fedora->getBaseUri(),
          '%status' => $response->getStatusCode(),
        ],
      ],
      ];
    }

    return [];
  }

}
