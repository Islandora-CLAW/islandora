<?php

namespace Drupal\islandora\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Config form for Islandora settings.
 */
class IslandoraSettingsForm extends ConfigFormBase {

  const CONFIG_NAME = 'islandora.settings';
  const BROKER_URL = 'broker_url';
  const TRIPLESTORE_INDEX_QUEUE = 'triplestore_index_queue';
  const FEDORA_REST_ENDPOINT = 'fedora_rest_endpoint';
  const FEDORA_INDEXING_QUEUE = 'fedora_indexing_queue';

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

    $form[self::BROKER_URL] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Broker URL'),
      '#default_value' => $config->get(self::BROKER_URL),
    );

    $form[self::TRIPLESTORE_INDEX_QUEUE] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Triplestore Index Queue'),
      '#default_value' => $config->get(self::TRIPLESTORE_INDEX_QUEUE),
    );

    $form[self::FEDORA_REST_ENDPOINT] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Fedora REST Endpoint'),
      '#description' => $this->t('The URL for your Fedora instance.'),
      '#default_value' => $config->get(self::FEDORA_REST_ENDPOINT),
    );

    $form[self::FEDORA_INDEXING_QUEUE] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Fedora Indexing Queue Name'),
      '#description' => $this->t('Name of the queue where Drupal will publish updates to have "indexed" to Fedora'),
      '#default_value' => $config->get(self::FEDORA_INDEXING_QUEUE),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable(self::CONFIG_NAME);

    $config
      ->set(self::BROKER_URL, $form_state->getValue(self::BROKER_URL))
      ->set(self::TRIPLESTORE_INDEX_QUEUE, $form_state->getValue(self::TRIPLESTORE_INDEX_QUEUE))
      ->set(self::FEDORA_REST_ENDPOINT, $form_state->getValue(self::FEDORA_REST_ENDPOINT))
      ->set(self::FEDORA_INDEXING_QUEUE, $form_state->getValue(self::FEDORA_INDEXING_QUEUE))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
