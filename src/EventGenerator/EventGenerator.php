<?php

namespace Drupal\islandora\EventGenerator;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
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
        "url" => $entity instanceof FileInterface ? $this->generateFileLinks($entity) : $this->generateRestLinks($entity),
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

  /**
   * Generates file urls.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file.
   *
   * @return array
   *   AS2 Links.
   */
  protected function generateFileLinks(FileInterface $file) {
    $file_url = $file->url();
    $checksum_url = Url::fromRoute('view.file_checksum.rest_export_1', ['file' => $file->id()])
      ->setAbsolute()
      ->toString();

    return [
      [
        "name" => "File",
        "type" => "Link",
        "href" => "$file_url",
        "mediaType" => $file->getMimeType(),
      ],
      [
        "name" => "Checksum",
        "type" => "Link",
        "href" => "$checksum_url?_format=json",
        "mediaType" => "application/json",
      ],
    ];
  }

  /**
   * Generates REST urls.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The media.
   *
   * @return array
   *   AS2 Links.
   */
  protected function generateRestLinks(EntityInterface $entity) {
    $url = $entity->toUrl()->setAbsolute()->toString();
    return [
        [
          "name" => "Canoncial",
          "type" => "Link",
          "href" => "$url",
          "mediaType" => "text/html",
          "rel" => "canonical",
        ],
        [
          "name" => "JSONLD",
          "type" => "Link",
          "href" => "$url?_format=jsonld",
          "mediaType" => "application/ld+json",
        ],
        [
          "name" => "JSON",
          "type" => "Link",
          "href" => "$url?_format=json",
          "mediaType" => "application/json",
        ],
    ];
  }

}
