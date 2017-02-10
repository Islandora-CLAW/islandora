<?php

namespace Drupal\islandora\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\islandora\RdfBundleSolver\JsonldContextGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FedoraResourceJsonLdContextController.
 *
 * @package Drupal\islandora\Controller
 */
class FedoraResourceJsonLdContextController extends ControllerBase {

  /**
   * Injected JsonldContextGenerator.
   *
   * @var \Drupal\islandora\RdfBundleSolver\JsonldContextGeneratorInterface
   */
  private $jsonldContextGenerator;

  /**
   * FedoraResourceJsonLdContextController constructor.
   *
   * @param \Drupal\islandora\RdfBundleSolver\JsonldContextGeneratorInterface $jsonld_context_generator
   *    Injected JsonldContextGenerator.
   */
  public function __construct(JsonldContextGeneratorInterface $jsonld_context_generator) {
    $this->jsonldContextGenerator = $jsonld_context_generator;
  }

  /**
   * Controller's create method for dependecy injection.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *    The App Container.
   *
   * @return static
   *    An instance of our islandora.jsonldcontextgenerator service.
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('islandora.jsonldcontextgenerator'));
  }

  /**
   * Returns an JSON-LD Context for a fedora_resource bundle.
   *
   * @param string $bundle
   *    Route argument, a bundle.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *    The Symfony Http Request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *    An Http response.
   */
  public function content($bundle, Request $request) {

    // TODO: expose cached/not cached through
    // more varied HTTP response codes.
    try {
      $context = $this->jsonldContextGenerator->getContext('fedora_resource.' . $bundle);
      $response = new Response($context, 200);
      $response->headers->set('X-Powered-By', 'Islandora CLAW API');
      $response->headers->set('Content-Type', 'application/ld+json');
    }
    catch (\Exception $e) {
      $response = new Response($e->getMessage(), 401);
    }

    return $response;
  }

}
