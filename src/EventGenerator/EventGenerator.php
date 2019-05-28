<?php

namespace Drupal\islandora\EventGenerator;

use Drupal\Core\Entity\EntityInterface;
use Drupal\file\FileInterface;
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
  public function generateEvent(EntityInterface $entity, UserInterface $user, array $data) {

    $user_url = $user->toUrl()->setAbsolute()->toString();

    if ($entity instanceof FileInterface) {
      $entity_url = $entity->url();
      $mimetype = $entity->getMimeType();
    }
    else {
      $entity_url = $entity->toUrl()->setAbsolute()->toString();
      $mimetype = 'text/html';
    }

    $event = [
      "@context" => "https://www.w3.org/ns/activitystreams",
      "actor" => [
        "type" => "Person",
        "id" => "urn:uuid:{$user->uuid()}",
        "url" => [
          [
            "name" => "Canonical",
            "type" => "Link",
            "href" => "$user_url",
            "mediaType" => "text/html",
            "rel" => "canonical",
          ],
        ],
      ],
      "object" => [
        "id" => "urn:uuid:{$entity->uuid()}",
        "url" => [
          [
            "name" => "Canonical",
            "type" => "Link",
            "href" => $entity_url,
            "mediaType" => $mimetype,
            "rel" => "canonical",
          ],
        ],
      ],
    ];

    $entity_type = $entity->getEntityTypeId();
    $event_type = $data["event"];
    if ($data["event"] == "Generate Derivative") {
      $event["type"] = "Activity";
      $event["summary"] = $data["event"];
    }
    else {
      $event["type"] = ucfirst($data["event"]);
      $event["summary"] = ucfirst($data["event"]) . " a " . ucfirst($entity_type);
    }

    // Add REST links for non-file entities.
    if ($entity_type != 'file') {
      $event['object']['url'][] = [
        "name" => "JSON",
        "type" => "Link",
        "href" => "$entity_url?_format=json",
        "mediaType" => "application/json",
        "rel" => "alternate",
      ];
      $event['object']['url'][] = [
        "name" => "JSONLD",
        "type" => "Link",
        "href" => "$entity_url?_format=jsonld",
        "mediaType" => "application/ld+json",
        "rel" => "alternate",
      ];
    }

    unset($data["event"]);
    unset($data["queue"]);

    if (!empty($data)) {
      $event["attachment"] = [
        "type" => "Object",
        "content" => $data,
        "mediaType" => "application/json",
      ];
    }

    return json_encode($event);
  }

}
