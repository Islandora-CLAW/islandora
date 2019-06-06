<?php

namespace Drupal\islandora\EventGenerator;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\islandora\MediaSource\MediaSourceService;
use Drupal\user\UserInterface;

/**
 * The default EventGenerator implementation.
 *
 * Provides Activity Stream 2.0 serialized events.
 */
class EventGenerator implements EventGeneratorInterface {

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Media source service.
   *
   * @var \Drupal\islandora\MediaSource\MediaSourceService
   */
  protected $mediaSource;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager.
   * @param \Drupal\islandora\MediaSource\MediaSourceService $media_source
   *   Media source service.
   */
  public function __construct(LanguageManagerInterface $language_manager, MediaSourceService $media_source) {
    $this->languageManager = $language_manager;
    $this->mediaSource = $media_source;
  }

  /**
   * {@inheritdoc}
   */
  public function generateEvent(EntityInterface $entity, UserInterface $user, array $data) {

    $user_url = $user->toUrl()->setAbsolute()->toString();
    $entity_type = $entity->getEntityTypeId();

    $undefined = $this->languageManager->getLanguage('und');

    if ($entity_type == 'file') {
      $entity_url = $entity->url('canonical', ['absolute' => TRUE, 'language' => $undefined]);
      $mimetype = $entity->getMimeType();
    }
    else {
      $entity_url = Url::fromRoute(
        "rest.entity.$entity_type.GET",
        [$entity_type => $entity->id()],
        ['absolute' => TRUE, 'language' => $undefined]
      )->toString();
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

    // Add a link to the file described by a media.
    if ($entity_type == 'media') {
      $file = $this->mediaSource->getSourceFile($entity);
      if ($file) {
        $event['object']['url'][] = [
          "name" => "Describes",
          "type" => "Link",
          "href" => $file->url('canonical', ['absolute' => TRUE, 'language' => $undefined]),
          "mediaType" => $file->getMimeType(),
          "rel" => "describes",
        ];
      }
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
