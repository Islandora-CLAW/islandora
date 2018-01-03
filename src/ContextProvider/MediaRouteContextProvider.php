<?php

namespace Drupal\islandora\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\media_entity\Entity\Media;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the current media as a context on media routes.
 */
class MediaRouteContextProvider implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new MediaRouteContextProvider.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $result = [];
    $context_definition = new ContextDefinition('entity:media', NULL, FALSE);
    $value = NULL;

    // Hack the media out of the route.
    $route_object = $this->routeMatch->getRouteObject();
    if ($route_object) {
      $route_contexts = $route_object->getOption('parameters');
      if ($route_contexts && isset($route_contexts['media'])) {
        $media = $this->routeMatch->getParameter('media');
        if ($media) {
          $value = $media;
        }
      }
      elseif ($this->routeMatch->getRouteName() == 'entity.media.add_form') {
        $media_bundle = $this->routeMatch->getParameter('media_bundle');
        $value = Media::create(['bundle' => $media_bundle->id()]);
      }
    }

    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);

    $context = new Context($context_definition, $value);
    $context->addCacheableDependency($cacheability);
    return ['media' => $context];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = new Context(new ContextDefinition('entity:media', $this->t('Media from URL')));
    return ['media' => $context];
  }

}
