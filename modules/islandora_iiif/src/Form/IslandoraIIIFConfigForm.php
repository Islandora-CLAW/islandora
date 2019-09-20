<?php

namespace Drupal\islandora_iiif\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use GuzzleHttp\Exception\ClientException;

/**
 * Class IslandoraIIIFConfigForm.
 */
class IslandoraIIIFConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'islandora_iiif.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_iiif_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('islandora_iiif.settings');
    $form['iiif_server'] = [
      '#type' => 'url',
      '#title' => $this->t('IIIF Image server location'),
      '#description' => $this->t('Please enter the image server location without trailing slash. e.g. http://www.example.org/iiif/2.'),
      '#default_value' => $config->get('iiif_server'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getValue('iiif_server'))) {
      $server = $form_state->getValue('iiif_server');
      if (!UrlHelper::isValid($server, UrlHelper::isExternal($server))) {
        $form_state->setErrorByName('iiif_server', "IIIF Server address is not a valid URL");
      }
      elseif (!$this->validateIiifUrl($server)) {
        $form_state->setErrorByName('iiif_server', "IIIF Server does not seem to be accessible.");
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('islandora_iiif.settings')
      ->set('iiif_server', $form_state->getValue('iiif_server'))
      ->save();
  }

  /**
   * Ensure the IIIF server is accessible.
   *
   * @param string $server_uri
   *   The absolute or relative URI to the server.
   *
   * @return bool
   *   True if server returns 200 on a HEAD request.
   */
  private function validateIiifUrl($server_uri) {
    $client = \Drupal::httpClient();
    try {
      $result = $client->head($server_uri);
      return ($result->getStatusCode() == 200);
    }
    catch (ClientException $e) {
      return FALSE;
    }

  }

}
