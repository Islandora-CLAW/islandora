<?php

namespace Drupal\islandora\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Stomp\Client;
use Stomp\Exception\StompException;
use Stomp\StatefulStomp;

/**
 * Config form for Islandora settings.
 */
class IslandoraSettingsForm extends ConfigFormBase {

  const CONFIG_NAME = 'islandora.settings';
  const BROKER_URL = 'broker_url';
  const JWT_EXPIRY = 'jwt_expiry';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      self::CONFIG_NAME,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_NAME);

    $form[self::BROKER_URL] = [
      '#type' => 'textfield',
      '#title' => $this->t('Broker URL'),
      '#default_value' => $config->get(self::BROKER_URL) ? $config->get(self::BROKER_URL) : 'tcp://localhost:61613',
    ];

    $form[self::JWT_EXPIRY] = [
      '#type' => 'textfield',
      '#title' => $this->t('JWT Expiry'),
      '#default_value' => $config->get(self::JWT_EXPIRY) ? $config->get(self::JWT_EXPIRY) : '+2 hour',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate broker url by actually connecting with a stomp client.
    $brokerUrl = $form_state->getValue(self::BROKER_URL);

    // Attempt to subscribe to a dummy queue.
    try {
      $stomp = new StatefulStomp(
        new Client(
          $brokerUrl
        )
      );
      $stomp->subscribe('dummy-queue-for-validation');
      $stomp->unsubscribe();
    }
    // Invalidate the form if there's an issue.
    catch (StompException $e) {
      $form_state->setErrorByName(
        self::BROKER_URL,
        $this->t(
          'Cannot connect to message broker at @broker_url',
          ['@broker_url' => $brokerUrl]
        )
      );
    }

    // Validate jwt expiry as a valid time string.
    $expiry = $form_state->getValue(self::JWT_EXPIRY);
    if (strtotime($expiry) === FALSE) {
      $form_state->setErrorByName(
        self::JWT_EXPIRY,
        $this->t(
          '"@exipry" is not a valid time or interval expression.',
          ['@expiry' => $expiry]
        )
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable(self::CONFIG_NAME);

    $config
      ->set(self::BROKER_URL, $form_state->getValue(self::BROKER_URL))
      ->set(self::JWT_EXPIRY, $form_state->getValue(self::JWT_EXPIRY))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
