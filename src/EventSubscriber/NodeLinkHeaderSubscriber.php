<?php

namespace Drupal\islandora\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Class NodeLinkHeaderSubscriber.
 *
 * @package Drupal\islandora\EventSubscriber
 */
class NodeLinkHeaderSubscriber extends LinkHeaderSubscriber implements EventSubscriberInterface {

  /**
   * Adds node-specific link headers to appropriate responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   Event containing the response.
   */
  public function onResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();

    $entity = $this->getObject($response, 'node');

    if ($entity === FALSE) {
      return;
    }

    // Use the node to add link headers for each entity reference.
    $bundle = $entity->bundle();

    // Get all fields for the entity.
    $fields = $this->entityFieldManager->getFieldDefinitions('node', $bundle);

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
          $links[] = "<$entity_url>; rel=\"related\"; title=\"$field_label\"";
        }
      }
    }

    // Exit early if there aren't any.
    if (empty($links)) {
      return;
    }

    // Add the link headers to the response.
    $response->headers->set('Link', $links, FALSE);
  }

}
