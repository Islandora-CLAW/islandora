<?php

namespace Drupal\islandora\ContextProvider;

use Drupal\media_entity\MediaInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the provided media as a context.
 */
class MediaContextProvider implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * Media to provide in a context.
   *
   * @var \Drupal\media_entity\MediaInterface
   */
  protected $media;

  /**
   * Constructs a new MediaRouteContext.
   *
   * @var \Drupal\media_entity\MediaInterface $media
   *   The media to provide in a context.
   */
  public function __construct(MediaInterface $media) {
    $this->media = $media;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $context_definition = new ContextDefinition('entity:media', NULL, FALSE);
    $context = new Context($context_definition, $this->media);
    return ['@islandora.media_route_context_provider:media' => $context];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = new Context(new ContextDefinition('entity:media', $this->t('Media from entity hook')));
    return ['@islandora.media_route_context_provider:media' => $context];
  }

}
