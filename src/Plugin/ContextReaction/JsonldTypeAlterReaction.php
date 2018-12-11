<?php

namespace Drupal\islandora\Plugin\ContextReaction;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\islandora\ContextReaction\NormalizerAlterReaction;
use Drupal\jsonld\Normalizer\NormalizerBase;

/**
 * Alter JSON-LD Type context reaction.
 *
 * @ContextReaction(
 *   id = "alter_jsonld_type",
 *   label = @Translation("Alter JSON-LD Type")
 * )
 */
class JsonldTypeAlterReaction extends NormalizerAlterReaction {

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Alter JSON-LD Type context reaction.');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(EntityInterface $entity = NULL, array &$normalized = NULL, array $context = NULL) {
    $config = $this->getConfiguration();
    if (($entity->hasField($config['source_field'])) &&
        (!empty($entity->get($config['source_field'])->getValue()))) {
      if (isset($normalized['@graph']) && is_array($normalized['@graph'])) {
        foreach ($normalized['@graph'] as &$graph) {
          foreach ($entity->get($config['source_field'])->getValue() as $type) {
            $graph['@type'][] = NormalizerBase::escapePrefix($type['value'], $context['namespaces']);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];
    $fieldsArray = \Drupal::service('entity_field.manager')->getFieldMap();
    foreach ($fieldsArray as $entity_type => $entity_fields) {
      foreach ($entity_fields as $field => $field_properties) {
        $options[$field] = $this->t('@field (@bundles)', [
          '@field' => $field,
          '@bundles' => implode(', ', array_keys($field_properties['bundles'])),
        ]);
      }
    }

    $config = $this->getConfiguration();
    $form['source_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Source Field'),
      '#options' => $options,
      '#description' => $this->t("A DESCRIPTION!"),
      '#default_value' => isset($config['source_field']) ? $config['source_field'] : '',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration(['source_field' => $form_state->getValue('source_field')]);
  }

}
