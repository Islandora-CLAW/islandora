<?php

/**
 * @file
 * Settings form for Islandora.
 */
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
      ->save();

    parent::submitForm($form, $form_state);
  }
}

