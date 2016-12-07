<?php

namespace Drupal\islandora\EventGenerator;

use Drupal\Core\Entity\EntityInterface;
use Drupal\user\UserInterface;

/**
 * Inteface for a service that provides serialized event messages.
 */
interface EventGeneratorInterface {

  /**
   * Generates a serialized 'Create' event.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that was created.
   * @param \Drupal\user\UserInterface $user
   *   The user who created the entity.
   *
   * @return string
   *   Serialized event message
   */
  public function generateCreateEvent(EntityInterface $entity, UserInterface $user);

  /**
   * Generates a serialized 'Create' event.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that was updated.
   * @param \Drupal\user\UserInterface $user
   *   The user who updated the entity.
   *
   * @return string
   *   Serialized event message
   */
  public function generateUpdateEvent(EntityInterface $entity, UserInterface $user);

  /**
   * Generates a serialized 'Create' event.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that was deleted.
   * @param \Drupal\user\UserInterface $user
   *   The user who deleted the entity.
   *
   * @return string
   *   Serialized event message
   */
  public function generateDeleteEvent(EntityInterface $entity, UserInterface $user);

}
