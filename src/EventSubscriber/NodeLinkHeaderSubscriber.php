<?php

namespace Drupal\islandora\EventSubscriber;

use Drupal\node\NodeInterface;
use Drupal\islandora\IslandoraUtils;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class NodeLinkHeaderSubscriber.
 *
 * @package Drupal\islandora\EventSubscriber
 */
class NodeLinkHeaderSubscriber extends LinkHeaderSubscriber implements EventSubscriberInterface {

  /**
   * Adds node-specific link headers to appropriate responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   Event containing the response.
   */
  public function onResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();

    $node = $this->getObject($response, 'node');

    if ($node === FALSE) {
      return;
    }

    $links = array_merge(
      $this->generateEntityReferenceLinks($node),
      $this->generateRelatedMediaLinks($node),
      $this->generateRestLinks($node)
    );

    // Add the link headers to the response.
    if (empty($links)) {
      return;
    }

    $response->headers->set('Link', $links, FALSE);
  }

  /**
   * Generates link headers for media associated with a node.
   */
  protected function generateRelatedMediaLinks(NodeInterface $node) {
    $links = [];
    foreach ($this->utils->getMedia($node) as $media) {
      $url = $this->utils->getEntityUrl($media);
      foreach ($media->referencedEntities() as $term) {
        if ($term->getEntityTypeId() == 'taxonomy_term' && $term->hasField(IslandoraUtils::EXTERNAL_URI_FIELD)) {
          $field = $term->get(IslandoraUtils::EXTERNAL_URI_FIELD);
          if (!$field->isEmpty()) {
            $link = $field->first()->getValue();
            $uri = $link['uri'];
            if (strpos($uri, 'http://pcdm.org/use#') === 0) {
              $title = $term->label();
              $links[] = "<$url>; rel=\"related\"; title=\"$title\"";
            }
          }
        }
      }
    }
    return $links;
  }

}
