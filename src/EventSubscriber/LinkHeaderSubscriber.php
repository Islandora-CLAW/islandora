<?php

namespace Drupal\islandora\EventSubscriber;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class LinkHeaderSubscriber.
 *
 * @package Drupal\islandora\EventSubscriber
 */
abstract class LinkHeaderSubscriber implements EventSubscriberInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   */
  public function __construct(
    EntityFieldManager $entity_field_manager,
    RouteMatchInterface $route_match
  ) {
    $this->entityFieldManager = $entity_field_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run this early so the headers get cached.
    $events[KernelEvents::RESPONSE][] = ['onResponse', 129];

    return $events;
  }

  /**
   * Get the Node | Media | File.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The current response object.
   * @param string $object_type
   *   The type of entity to look for.
   *
   * @return Drupal\Core\Entity\ContentEntityBase|bool
   *   A node or media entity or FALSE if we should skip out.
   */
  protected function getObject(Response $response, $object_type) {
    if ($object_type != 'node'
      && $object_type != 'media'
    ) {
      return FALSE;
    }

    // Exit early if the response is already cached.
    if ($response->headers->get('X-Drupal-Dynamic-Cache') == 'HIT') {
      return FALSE;
    }

    if (!$response->isOk()) {
      return FALSE;
    }

    // Hack the node out of the route.
    $route_object = $this->routeMatch->getRouteObject();
    if (!$route_object) {
      return FALSE;
    }

    $methods = $route_object->getMethods();
    $is_get = in_array('GET', $methods);
    $is_head = in_array('HEAD', $methods);
    if (!($is_get || $is_head)) {
      return FALSE;
    }

    $route_contexts = $route_object->getOption('parameters');
    if (!$route_contexts) {
      return FALSE;
    }
    if (!isset($route_contexts[$object_type])) {
      return FALSE;
    }

    $object = $this->routeMatch->getParameter($object_type);

    if (!$object) {
      return FALSE;
    }

    return $object;
  }

  /**
   * Adds resource-specific link headers to appropriate responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   Event containing the response.
   */
  abstract public function onResponse(FilterResponseEvent $event);

}
