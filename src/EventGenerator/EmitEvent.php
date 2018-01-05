<?php

namespace Drupal\islandora\EventGenerator;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jwt\Authentication\Provider\JwtAuth;
use Stomp\Exception\StompException;
use Stomp\StatefulStomp;
use Stomp\Transport\Message;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configurable action base for actions that publish messages to queues.
 */
abstract class EmitEvent extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * User storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * Event generator service.
   *
   * @var \Drupal\islandora\EventGenerator\EventGeneratorInterface
   */
  protected $eventGenerator;

  /**
   * Stomp client.
   *
   * @var \Stomp\StatefulStomp
   */
  protected $stomp;

  /**
   * The JWT Auth Service.
   *
   * @var \Drupal\jwt\Authentication\Provider\JwtAuth
   */
  protected $auth;

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
   * @param \Drupal\Core\Entity\EntityStorageInterface $user_storage
   *   User storage.
   * @param \Drupal\islandora\EventGenerator\EventGeneratorInterface $event_generator
   *   EventGenerator service to serialize AS2 events.
   * @param \Stomp\StatefulStomp $stomp
   *   Stomp client.
   * @param \Drupal\jwt\Authentication\Provider\JwtAuth $auth
   *   JWT Auth client.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountInterface $account,
    EntityStorageInterface $user_storage,
    EventGeneratorInterface $event_generator,
    StatefulStomp $stomp,
    JwtAuth $auth
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
    $this->userStorage = $user_storage;
    $this->eventGenerator = $event_generator;
    $this->stomp = $stomp;
    $this->auth = $auth;
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
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('islandora.eventgenerator'),
      $container->get('islandora.stomp'),
      $container->get('jwt.authentication.jwt')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {

    // Include a token for later authentication in the message.
    $token = $this->auth->generateToken();
    if (empty($token)) {
      // JWT isn't properly configured. Log and notify user.
      \Drupal::logger('islandora')->error(
        t('Error getting JWT token for message. Check JWT Configuration.')
      );
      drupal_set_message(
        t('Error getting JWT token for message. Check JWT Configuration.'), 'error'
      );
      return;
    }

    // Generate the event message.
    $user = $this->userStorage->load($this->account->id());

    if ($this->configuration['event'] == 'create') {
      $message = $this->eventGenerator->generateCreateEvent($entity, $user);
    }
    elseif ($this->configuration['event'] == 'update') {
      $message = $this->eventGenerator->generateUpdateEvent($entity, $user);
    }
    elseif ($this->configuration['event'] == 'delete') {
      $message = $this->eventGenerator->generateDeleteEvent($entity, $user);
    }

    // Transform message from string into a proper message object.
    $message = new Message($message, ['Authorization' => "Bearer $token"]);

    // Send the message.
    try {
      $this->stomp->begin();
      $this->stomp->send($this->configuration['queue'], $message);
      $this->stomp->commit();
    }
    catch (StompException $e) {
      // Log it.
      \Drupal::logger('islandora')->error(
        'Error publishing message: @msg',
        ['@msg' => $e->getMessage()]
      );

      // Notify user.
      drupal_set_message(
        t('Error publishing message: @msg',
          ['@msg' => $e->getMessage()]
        ),
        'error'
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'queue' => '',
      'event' => 'create',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['queue'] = [
      '#type' => 'textfield',
      '#title' => t('Queue'),
      '#default_value' => $this->configuration['queue'],
      '#required' => TRUE,
      '#rows' => '8',
      '#description' => t('Name of queue to which event is published'),
    ];
    $form['event'] = [
      '#type' => 'select',
      '#title' => t('Event type'),
      '#default_value' => $this->configuration['event'],
      '#description' => t('Type of event to emit'),
      '#options' => [
        'create' => t('Create'),
        'update' => t('Update'),
        'delete' => t('Delete'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['queue'] = $form_state->getValue('queue');
    $this->configuration['event'] = $form_state->getValue('event');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowed();
    return $return_as_object ? $result : $result->isAllowed();
  }

}
