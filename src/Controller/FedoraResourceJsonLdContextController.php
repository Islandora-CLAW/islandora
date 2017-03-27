<?php

namespace Drupal\islandora\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\islandora\JsonldContextGenerator\JsonldContextGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\rdf\Entity\RdfMapping;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Class FedoraResourceJsonLdContextController.
 *
 * @package Drupal\islandora\Controller
 */
class FedoraResourceJsonLdContextController extends ControllerBase {

  /**
   * Injected JsonldContextGenerator.
   *
   * @var \Drupal\islandora\JsonldContextGenerator\JsonldContextGeneratorInterface
   */
  private $jsonldContextGenerator;

  /**
   * FedoraResourceJsonLdContextController constructor.
   *
   * @param \Drupal\islandora\JsonldContextGenerator\JsonldContextGeneratorInterface $jsonld_context_generator
   *   Injected JsonldContextGenerator.
   */
  public function __construct(JsonldContextGeneratorInterface $jsonld_context_generator) {
    $this->jsonldContextGenerator = $jsonld_context_generator;
  }

  /**
   * Controller's create method for dependecy injection.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The App Container.
   *
   * @return static
   *   An instance of our islandora.jsonldcontextgenerator service.
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('islandora.jsonldcontextgenerator'));
  }

  /**
   * Returns an JSON-LD Context for a fedora_resource bundle.
   *
   * @param string $bundle
   *   Route argument, a bundle.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Symfony Http Request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   An Http response.
   */
  public function content($bundle, Request $request) {

    // TODO: expose cached/not cached through
    // more varied HTTP response codes.
    try {
      $context = $this->jsonldContextGenerator->getContext('fedora_resource.' . $bundle);
      $response = new CacheableJsonResponse(json_decode($context), 200);
      $response->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
      $response->headers->set('X-Powered-By', 'Islandora CLAW API');
      $response->headers->set('Content-Type', 'application/ld+json');

      // For now deal with Cache dependencies manually.
      $meta = new CacheableMetadata();
      $meta->setCacheContexts(['user.permissions', 'ip', 'url']);
      $meta->setCacheTags(RdfMapping::load('fedora_resource.' . $bundle)->getCacheTags());
      $meta->setCacheMaxAge(Cache::PERMANENT);
      $response->addCacheableDependency($meta);
    }
    catch (\Exception $e) {
      $response = new Response($e->getMessage(), 400);
    }

    return $response;
  }

}
