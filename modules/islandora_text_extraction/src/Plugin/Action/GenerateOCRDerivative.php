<?php

namespace Drupal\islandora_text_extraction\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\islandora\Plugin\Action\AbstractGenerateDerivative;

/**
 * Emits a Node for generating OCR derivatives event.
 *
 * @Action(
 *   id = "generate_ocr_derivative",
 *   label = @Translation("Get OCR from image"),
 *   type = "node"
 * )
 */
class GenerateOCRDerivative extends AbstractGenerateDerivative {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['path'] = '[date:custom:Y]-[date:custom:m]/[node:nid]-[term:name].txt';
    $config['mimetype'] = 'application/xml';
    $config['queue'] = 'islandora-connector-ocr';
    $config['destination_media_type'] = 'file';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['mimetype']['#description'] = t('Mimetype to convert to (e.g. application/xml, etc...)');
    $form['mimetype']['#value'] = 'text/plain';
    $form['mimetype']['#type'] = 'textfield';

    unset($form['args']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $exploded_mime = explode('/', $form_state->getValue('mimetype'));
    if ($exploded_mime[0] != 'text') {
      $form_state->setErrorByName(
        'mimetype',
        t('Please enter file mimetype (e.g. text/plain.)')
      );
    }
  }

}
