<?php

namespace Drupal\islandora\EventSubscriber;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   */
  public function __construct(
    EntityTypeManager $entity_type_manager,
    EntityFieldManager $entity_field_manager,
    AccessManagerInterface $access_manager,
    AccountInterface $account,
    RouteMatchInterface $route_match
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->accessManager = $access_manager;
    $this->account = $account;
    $this->routeMatch = $route_match;
    $this->accessManager = $access_manager;
    $this->account = $account;
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
   * Generates link headers for each referenced entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity that has reference fields.
   *
   * @return string[]
   *   Array of link headers
   */
  protected function generateEntityReferenceLinks(EntityInterface $entity) {
    // Use the node to add link headers for each entity reference.
    $entity_type = $entity->getEntityType()->id();
    $bundle = $entity->bundle();

    // Get all fields for the entity.
    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);

    // Strip out everything but entity references that are not base fields.
    $entity_reference_fields = array_filter($fields, function ($field) {
      return $field->getFieldStorageDefinition()->isBaseField() == FALSE && $field->getType() == "entity_reference";
    });

    // Collect links for referenced entities.
    $links = [];
    foreach ($entity_reference_fields as $field_name => $field_definition) {
      foreach ($entity->get($field_name)->referencedEntities() as $referencedEntity) {
        // Headers are subject to an access check.
        if ($referencedEntity->access('view')) {
          $entity_url = $referencedEntity->url('canonical', ['absolute' => TRUE]);
          $field_label = $field_definition->label();
          $links[] = "<$entity_url>; rel=\"related\"; title=\"$field_label\"; field=\"{$field_name}\"";
        }
      }
    }

    return $links;
  }

  /**
   * Generates link headers for REST endpoints.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity that has reference fields.
   *
   * @return string[]
   *   Array of link headers
   */
  protected function generateRestLinks(EntityInterface $entity) {
    $rest_resource_config_storage = $this->entityTypeManager->getStorage('rest_resource_config');
    $entity_type = $entity->getEntityType()->id();
    $rest_resource_config = $rest_resource_config_storage->load("entity.$entity_type");

    $links = [];
    $route_name = $this->routeMatch->getRouteName();

    if ($rest_resource_config) {
      $formats = $rest_resource_config->getFormats("GET");

      foreach ($formats as $format) {
        switch ($format) {
          case 'json':
            $mime = 'application/json';
            break;

          case 'jsonld':
            $mime = 'application/ld+json';
            break;

          case 'hal_json':
            $mime = 'application/hal+json';
            break;

          case 'xml':
            $mime = 'application/xml';
            break;

          default:
            continue;
        }

        $meta_route_name = "rest.entity.$entity_type.GET.$format";

        if ($route_name == $meta_route_name) {
          continue;
        }

        $route_params = [$entity_type => $entity->id()];

        if (!$this->accessManager->checkNamedRoute($meta_route_name, $route_params, $this->account)) {
          continue;
        }

        $meta_url = Url::fromRoute($meta_route_name, $route_params)
          ->setAbsolute()
          ->toString();

        $links[] = "<$meta_url?_format=$format>; rel=\"alternate\"; type=\"$mime\"";
      }
    }

    return $links;
  }

  /**
   * Adds resource-specific link headers to appropriate responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   Event containing the response.
   */
  abstract public function onResponse(FilterResponseEvent $event);

}
