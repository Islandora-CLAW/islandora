<?php

namespace Drupal\islandora\ViewAlter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFieldManager;

/**
 * Adds a rel="related" link header for each entity reference to responses.
 */
class RelatedLinkHeader extends LinkHeaderAlter implements ViewAlterInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityFieldManager $entity_field_manager) {
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function alter(array &$build, EntityInterface $entity) {
    $entity_type_id = $entity->getEntityTypeId();
    $bundle = $entity->bundle();

    // Get all fields for the entity.
    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);

    // Strip out everything but entity references that are not base fields.
    $entity_reference_fields = array_filter($fields, function ($field) {
      return $field->getFieldStorageDefinition()->isBaseField() == FALSE && $field->getType() == "entity_reference";
    });

    // Collect links for referenced entities.
    $links = [];
    foreach ($entity_reference_fields as $field_name => $field_definition) {
      foreach ($entity->get($field_name)->referencedEntities() as $referencedEntity) {
        if ($entity->access('view')) {
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

    // Assemble and add as 'Link' header if it already exists.
    $header_str = implode(", ", $links);
    $this->addLinkHeaders($build, $header_str);
  }

}
