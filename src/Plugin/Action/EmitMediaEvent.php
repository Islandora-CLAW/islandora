<?php

namespace Drupal\islandora\Plugin\Action;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jwt\Authentication\Provider\JwtAuth;
use Drupal\islandora\EventGenerator\EmitEvent;
use Drupal\islandora\EventGenerator\EventGeneratorInterface;
use Drupal\islandora\MediaSource\MediaSourceService;
use Stomp\StatefulStomp;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Emits a Media event.
 *
 * @Action(
 *   id = "emit_media_event",
 *   label = @Translation("Emit a media event to a queue/topic"),
 *   type = "media"
 * )
 */
class EmitMediaEvent extends EmitEvent {

  /**
   * Media source service.
   *
   * @var \Drupal\islandora\MediaSource\MediaSourceService
   */
  protected $mediaSource;

  /**
   * Constructs a EmitEvent action.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\islandora\EventGenerator\EventGeneratorInterface $event_generator
   *   EventGenerator service to serialize AS2 events.
   * @param \Stomp\StatefulStomp $stomp
   *   Stomp client.
   * @param \Drupal\jwt\Authentication\Provider\JwtAuth $auth
   *   JWT Auth client.
   * @param \Drupal\islandora\MediaSource\MediaSourceService $media_source
   *   Media source service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountInterface $account,
    EntityTypeManagerInterface $entity_type_manager,
    EventGeneratorInterface $event_generator,
    StatefulStomp $stomp,
    JwtAuth $auth,
    MediaSourceService $media_source
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $account,
      $entity_type_manager,
      $event_generator,
      $stomp,
      $auth
    );
    $this->mediaSource = $media_source;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('islandora.eventgenerator'),
      $container->get('islandora.stomp'),
      $container->get('jwt.authentication.jwt'),
      $container->get('islandora.media_source_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function generateData(EntityInterface $entity) {
    $data = parent::generateData($entity);
    $data['source_field'] = $this->mediaSource->getSourceFieldName($entity->bundle());
    return $data;
  }

}
