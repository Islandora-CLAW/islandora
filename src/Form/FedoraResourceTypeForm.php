<?php

namespace Drupal\islandora\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FedoraResourceTypeForm.
 *
 * @package Drupal\islandora\Form
 */
class FedoraResourceTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $fedora_resource_type = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $fedora_resource_type->label(),
      '#description' => $this->t("Label for the Fedora resource type."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $fedora_resource_type->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\islandora\Entity\FedoraResourceType::load',
      ),
      '#disabled' => !$fedora_resource_type->isNew(),
    );

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $fedora_resource_type = $this->entity;
    $status = $fedora_resource_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Fedora resource type.', [
          '%label' => $fedora_resource_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Fedora resource type.', [
          '%label' => $fedora_resource_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($fedora_resource_type->urlInfo('collection'));
  }

}
