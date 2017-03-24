<?php

namespace Drupal\islandora\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\islandora\IslandoraConstants;
use Drupal\islandora\VersionCounter\VersionCounter;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\rest\resource\EntityResource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Defines a Fedora RestResource handler.
 *
 * @RestResource(
 *   id = "fedora_resource",
 *   label = @Translation("Fedora Resource"),
 *   serialization_class = "Drupal\islandora\Entity\FedoraResource",
 *   uri_paths = {
 *     "canonical" = "/fedora_resource/{id}",
 *     "https://www.drupal.org/link-relations/create" = "/entity/fedora_resource"
 *   }
 * )
 */
class FedoraResourceEntity extends EntityResource {

  /**
   * {@inheritdoc}
   */
  public function patch(EntityInterface $original_entity, EntityInterface $entity = NULL, Request $request) {
    if ($entity == NULL) {
      throw new BadRequestHttpException('No entity content received.');
    }
    $definition = $this->getPluginDefinition();
    if ($entity->getEntityTypeId() != $definition['entity_type']) {
      throw new BadRequestHttpException('Invalid entity type');
    }
    if (!$original_entity->access('update')) {
      throw new AccessDeniedHttpException();
    }
    if (!$request->headers->has(IslandoraConstants::ISLANDORA_VCLOCK_HEADER)) {
      throw new BadRequestHttpException('No ' . IslandoraConstants::ISLANDORA_VCLOCK_HEADER . ' header provided.');
    }
    else {
      $uuid = $entity->uuid();
      $counter = new VersionCounter();
      if ($counter->isValid($uuid, $request->headers->get(IslandoraConstants::ISLANDORA_VCLOCK_HEADER) === 0)) {
        throw new ConflictHttpException(IslandoraConstants::ISLANDORA_VCLOCK_HEADER . ' value does not match.');
      }
    }

    // Overwrite the received properties.
    $entity_keys = $entity->getEntityType()->getKeys();
    foreach ($entity->_restSubmittedFields as $field_name) {
      $field = $entity->get($field_name);

      // Entity key fields need special treatment: together they uniquely
      // identify the entity. Therefore it does not make sense to modify any of
      // them. However, rather than throwing an error, we just ignore them as
      // long as their specified values match their current values.
      if (in_array($field_name, $entity_keys, TRUE)) {
        // Unchanged values for entity keys don't need access checking.
        if ($original_entity->get($field_name)->getValue() === $entity->get($field_name)->getValue()) {
          continue;
        }
        // It is not possible to set the language to NULL as it is automatically
        // re-initialized. As it must not be empty, skip it if it is.
        elseif (isset($entity_keys['langcode']) && $field_name === $entity_keys['langcode'] && $field->isEmpty()) {
          continue;
        }
      }

      if (!$original_entity->get($field_name)->access('edit')) {
        throw new AccessDeniedHttpException("Access denied on updating field '$field_name'.");
      }
      $original_entity->set($field_name, $field->getValue());
    }

    // Validate the received data before saving.
    $this->validate($original_entity);
    try {
      $original_entity->save();
      $this->logger->notice('Updated entity %type with ID %id.', array('%type' => $original_entity->getEntityTypeId(), '%id' => $original_entity->id()));

      // Return the updated entity in the response body.
      return new ModifiedResourceResponse($original_entity, 200);
    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, 'Internal Server Error', $e);
    }
  }

}
