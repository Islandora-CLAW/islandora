<?php

namespace Drupal\islandora\EventGenerator;

use Drupal\Core\Entity\EntityInterface;
use Drupal\media_entity\Entity\Media;
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
    $event = [
      "@context" => "https://www.w3.org/ns/activitystreams",
      "type" => "Create",
      "actor" => [
        "type" => "Person",
        "id" => $user->toUrl()->setAbsolute()->toString(),
      ],
      "object" => $entity->toUrl()->setAbsolute()->toString(),
    ];

    if ($entity instanceof Media) {
      $this->addAttachment($entity, $event);
    }

    return json_encode($event);
  }

  /**
   * {@inheritdoc}
   */
  public function generateUpdateEvent(EntityInterface $entity, UserInterface $user) {
    $event = [
      "@context" => "https://www.w3.org/ns/activitystreams",
      "type" => "Update",
      "actor" => [
        "type" => "Person",
        "id" => $user->toUrl()->setAbsolute()->toString(),
      ],
      "object" => $entity->toUrl()->setAbsolute()->toString(),
    ];

    if ($entity instanceof Media) {
      $this->addAttachment($entity, $event);
    }

    return json_encode($event);
  }

  /**
   * {@inheritdoc}
   */
  public function generateDeleteEvent(EntityInterface $entity, UserInterface $user) {
    $event = [
      "@context" => "https://www.w3.org/ns/activitystreams",
      "type" => "Delete",
      "actor" => [
        "type" => "Person",
        "id" => $user->toUrl()->setAbsolute()->toString(),
      ],
      "object" => $entity->toUrl()->setAbsolute()->toString(),
    ];

    if ($entity instanceof Media) {
      $this->addAttachment($entity, $event);
    }

    return json_encode($event);
  }

  /**
   * Adds the 'attachment' info to the event array.
   *
   * @param \Drupal\media_entity\Entity\Media $entity
   *   The entity that was updated.
   * @param array $event
   *   Array of info to be serialized to jsonld.
   */
  protected function addAttachment(Media $entity, array &$event) {
    if ($entity->hasField("field_image")) {
      $file = $entity->field_image->entity;
    }
    elseif ($entity->hasField("field_file")) {
      $file = $entity->field_file->entity;
    }
    else {
      \Drupal::logger('islandora')->warning(
        "Cannot parse 'field_image' or 'field_file' from Media entity {$entity->id()}"
      );
      return;
    }

    if ($file === NULL) {
      \Drupal::logger('islandora')->debug(
        "'field_image' or 'field_file' is null in Media entity {$entity->id()}"
      );
      return;
    }

    $url = file_create_url($file->getFileUri());
    $mime = $file->getMimeType();
    $event['attachment'] = [
      'url' => $url,
      'mediaType' => $mime,
    ];
  }

}
