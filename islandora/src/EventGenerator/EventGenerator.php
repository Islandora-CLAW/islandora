<?php

namespace Drupal\islandora\EventGenerator;

use Drupal\Core\Entity\EntityInterface;
use Drupal\user\UserInterface;

/**
 * The default EventGenerator implementation.
 *
 * Provides Activity Stream 2.0 serialized events.
 */
class EventGenerator implements EventGeneratorInterface {

  /**
   * {@inheritdoc}
   */
  public function generateCreateEvent(EntityInterface $entity, UserInterface $user) {
    return json_encode([
      "@context" => "https://www.w3.org/ns/activitystreams",
      "type" => "Create",
      "actor" => [
        "type" => "Person",
        "id" => $user->toUrl()->setAbsolute()->toString(),
      ],
      "object" => $entity->toUrl()->setAbsolute()->toString(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function generateUpdateEvent(EntityInterface $entity, UserInterface $user) {
    return json_encode([
      "@context" => "https://www.w3.org/ns/activitystreams",
      "type" => "Update",
      "actor" => [
        "type" => "Person",
        "id" => $user->toUrl()->setAbsolute()->toString(),
      ],
      "object" => $entity->toUrl()->setAbsolute()->toString(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function generateDeleteEvent(EntityInterface $entity, UserInterface $user) {
    return json_encode([
      "@context" => "https://www.w3.org/ns/activitystreams",
      "type" => "Delete",
      "actor" => [
        "type" => "Person",
        "id" => $user->toUrl()->setAbsolute()->toString(),
      ],
      "object" => $entity->toUrl()->setAbsolute()->toString(),
    ]);
  }

}
