<?php

namespace Drupal\islandora\EventSubscriber;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Class MediaLinkHeaderSubscriber.
 *
 * @package Drupal\islandora\EventSubscriber
 */
class MediaLinkHeaderSubscriber extends LinkHeaderSubscriber implements EventSubscriberInterface {

  /**
   * Media storage interface.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mediaBundleStorage;

  /**
   * MediaLinkHeaderSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    EntityFieldManager $entity_field_manager,
    RouteMatchInterface $route_match,
    EntityTypeManagerInterface $entity_type_manager) {
    $this->mediaBundleStorage = $entity_type_manager->getStorage('media_bundle');
    parent::__construct($entity_field_manager, $route_match);
  }

  /**
   * {@inheritdoc}
   */
  public function onResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();

    $entity = $this->getObject($response, 'media');

    if ($entity === FALSE) {
      return;
    }

    $media_bundle = $this->mediaBundleStorage->load($entity->bundle());

    $type_configuration = $media_bundle->getTypeConfiguration();

    if (!isset($type_configuration['source_field'])) {
      return;
    }
    $source_field = $type_configuration['source_field'];

    if (empty($source_field) ||
      !$entity instanceof FieldableEntityInterface ||
      !$entity->hasField($source_field)
    ) {
      return;
    }

    // Collect file links for the media.
    $links = [];
    foreach ($entity->get($source_field)->referencedEntities() as $referencedEntity) {
      if ($entity->access('view')) {
        $file_url = $referencedEntity->url('canonical', ['absolute' => TRUE]);
        $edit_media_url = Url::fromRoute('islandora.media_source_update', ['media' => $referencedEntity->id()])
          ->setAbsolute()
          ->toString();
        $links[] = "<$file_url>; rel=\"describes\"; type=\"{$referencedEntity->getMimeType()}\"";
        $links[] = "<$edit_media_url>; rel=\"edit-media\"";
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
