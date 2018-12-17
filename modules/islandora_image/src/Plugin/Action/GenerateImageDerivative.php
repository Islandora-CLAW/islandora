<?php

namespace Drupal\islandora_image\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\islandora\Plugin\Action\AbstractGenerateDerivative;

/**
 * Emits a Node event.
 *
 * @Action(
 *   id = "generate_image_derivative",
 *   label = @Translation("Generate an image derivative"),
 *   type = "node"
 * )
 */
class GenerateImageDerivative extends AbstractGenerateDerivative {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['mimetype'] = 'image/jpeg';
    $config['path'] = '[date:custom:Y]-[date:custom:m]/[node:nid].jpg';
    $config['destination_media_type'] = 'image';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['mimetype']['#description'] = t('Mimetype to convert to (e.g. image/jpeg, image/png, etc...)');
    $form['args']['#description'] = t('Additional command line arguments for ImageMagick convert (e.g. -resize 50%');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $exploded_mime = explode('/', $form_state->getValue('mimetype'));

    if ($exploded_mime[0] != "image") {
      $form_state->setErrorByName(
        'mimetype',
        t('Please enter an image mimetype (e.g. image/jpeg, image/png, etc...)')
      );
    }
  }

}
