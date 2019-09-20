<?php

namespace Drupal\islandora\EventSubscriber;

use Drupal\Core\Url;
use Drupal\media\MediaInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Class MediaLinkHeaderSubscriber.
 *
 * @package Drupal\islandora\EventSubscriber
 */
class MediaLinkHeaderSubscriber extends LinkHeaderSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public function onResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();

    $media = $this->getObject($response, 'media');

    if ($media === FALSE) {
      return;
    }

    $links = array_merge(
      $this->generateEntityReferenceLinks($media),
      $this->generateRestLinks($media),
      $this->generateMediaLinks($media)
    );

    // Add the link headers to the response.
    if (empty($links)) {
      return;
    }

    $response->headers->set('Link', $links, FALSE);
  }

  /**
   * Generates link headers for the described file and source update routes.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media to generate link headers.
   *
   * @return string[]
   *   Array of link headers
   */
  protected function generateMediaLinks(MediaInterface $media) {
    $media_type = $this->entityTypeManager->getStorage('media_type')->load($media->bundle());

    $type_configuration = $media_type->get('source_configuration');

    $links = [];

    $update_route_name = 'islandora.media_source_update';
    $update_route_params = ['media' => $media->id()];
    if ($this->accessManager->checkNamedRoute($update_route_name, $update_route_params, $this->account)) {
      $edit_media_url = Url::fromRoute($update_route_name, $update_route_params)
        ->setAbsolute()
        ->toString();
      $links[] = "<$edit_media_url>; rel=\"edit-media\"";
    }

    if (!isset($type_configuration['source_field'])) {
      return $links;
    }
    $source_field = $type_configuration['source_field'];

    if (empty($source_field) ||
      !$media->hasField($source_field) ||
      !method_exists($media->get($source_field), 'referencedEntities')
    ) {
      return $links;
    }

    // Collect file links for the media.
    foreach ($media->get($source_field)->referencedEntities() as $referencedEntity) {
      if ($referencedEntity->access('view')) {
        $file_url = $this->utils->getDownloadUrl($referencedEntity);
        $links[] = "<$file_url>; rel=\"describes\"; type=\"{$referencedEntity->getMimeType()}\"";
      }
    }

    return $links;
  }

}
