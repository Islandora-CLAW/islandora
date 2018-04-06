<?php

namespace Drupal\islandora\EventGenerator;

use Drupal\Core\Entity\EntityInterface;
use Drupal\user\UserInterface;

/**
 * Inteface for a service that provides serialized AS2 messages.
 */
interface EventGeneratorInterface {

  /**
   * Generates a serialized event.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity in the action.
   * @param \Drupal\user\UserInterface $user
   *   The user performing the action.
   * @param array $data
   *   Arbitrary data to serialize within the event.
   *
   * @return string
   *   Serialized event message
   */
  public function generateEvent(EntityInterface $entity, UserInterface $user, array $data);

}
