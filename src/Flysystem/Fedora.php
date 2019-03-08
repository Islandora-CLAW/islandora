<?php

namespace Drupal\islandora\Flysystem;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\flysystem\Plugin\FlysystemPluginInterface;
use Drupal\flysystem\Plugin\FlysystemUrlTrait;
use Drupal\islandora\Flysystem\Adapter\FedoraAdapter;
use Drupal\jwt\Authentication\Provider\JwtAuth;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Client;
use Islandora\Chullo\IFedoraApi;
use Islandora\Chullo\FedoraApi;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

/**
 * Drupal plugin for the Fedora Flysystem adapter.
 *
 * @Adapter(id = "fedora")
 */
class Fedora implements FlysystemPluginInterface, ContainerFactoryPluginInterface {

  use FlysystemUrlTrait;

  protected $fedora;

  protected $mimeTypeGuesser;

  /**
   * Constructs a Fedora plugin for Flysystem.
   *
   * @param \Islandora\Chullo\IFedoraApi $fedora
   *   Fedora client.
   * @param \Symfony\Component\HttpFoundation\File\Mimetype\MimeTypeGuesserInterface $mime_type_guesser
   *   Mimetype guesser.
   */
  public function __construct(
    IFedoraApi $fedora,
    MimeTypeGuesserInterface $mime_type_guesser
  ) {
    $this->fedora = $fedora;
    $this->mimeTypeGuesser = $mime_type_guesser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    // Construct guzzle client to middleware that adds JWT.
    $stack = HandlerStack::create();
    $stack->push(static::addJwt($container->get('jwt.authentication.jwt')));
    $client = new Client([
      'handler' => $stack,
      'base_uri' => $configuration['root'],
    ]);
    $fedora = new FedoraApi($client);

    // Return it.
    return new static(
      $fedora,
      $container->get('file.mime_type.guesser')
    );
  }

  /**
   * Guzzle middleware to add a header to outgoing requests.
   *
   * @param \Drupal\jwt\Authentication\Provider\JwtAuth $jwt
   *   JWT.
   */
  public static function addJwt(JwtAuth $jwt) {
    return function (callable $handler) use ($jwt) {
      return function (
        RequestInterface $request,
        array $options
      ) use (
        $handler,
        $jwt
      ) {
        $request = $request->withHeader('Authorization', 'Bearer ' . $jwt->generateToken());
        return $handler($request, $options);
      };
    };
  }

  /**
   * {@inheritdoc}
   */
  public function getAdapter() {
    return new FedoraAdapter($this->fedora, $this->mimeTypeGuesser);
  }

  /**
   * {@inheritdoc}
   */
  public function ensure($force = FALSE) {
    // Check fedora root for sanity.
    $response = $this->fedora->getResourceHeaders('');

    if ($response->getStatusCode() != 200) {
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
