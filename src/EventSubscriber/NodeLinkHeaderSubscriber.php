<?php

namespace Drupal\islandora\EventSubscriber;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\islandora\IslandoraUtils;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class NodeLinkHeaderSubscriber.
 *
 * @package Drupal\islandora\EventSubscriber
 */
class NodeLinkHeaderSubscriber extends LinkHeaderSubscriber implements EventSubscriberInterface {

  /**
   * Derivative utils.
   *
   * @var \Drupal\islandora\IslandoraUtils
   */
  protected $utils;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   * @param Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack (for current request).
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager.
   * @param \Drupal\islandora\IslandoraUtils $utils
   *   Derivative utils.
   */
  public function __construct(
    EntityTypeManager $entity_type_manager,
    EntityFieldManager $entity_field_manager,
    AccessManagerInterface $access_manager,
    AccountInterface $account,
    RouteMatchInterface $route_match,
    RequestStack $request_stack,
    LanguageManagerInterface $language_manager,
    IslandoraUtils $utils
  ) {
    parent::__construct(
      $entity_type_manager,
      $entity_field_manager,
      $access_manager,
      $account,
      $route_match,
      $request_stack,
      $language_manager
    );
    $this->utils = $utils;
  }

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
    $undefined = $this->languageManager->getLanguage('und');
    foreach ($this->utils->getMedia($node) as $media) {
      $url = Url::fromRoute(
        "rest.entity.media.GET",
        ['media' => $media->id()],
        ['absolute' => TRUE, 'language' => $undefined]
      )->toString();
      foreach ($media->referencedEntities() as $term) {
        if ($term->getEntityTypeId() == 'taxonomy_term' && $term->hasField('field_external_uri')) {
          $field = $term->get('field_external_uri');
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
