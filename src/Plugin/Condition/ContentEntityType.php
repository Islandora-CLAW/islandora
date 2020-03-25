<?php

namespace Drupal\islandora\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Content Entity Type' condition.
 *
 * @Condition(
 *   id = "content_entity_type",
 *   label = @Translation("Content Entity Type"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", required = FALSE, label = @Translation("Node")),
 *     "media" = @ContextDefinition("entity:media", required = FALSE, label = @Translation("Media")),
 *     "file" = @ContextDefinition("entity:file", required = FALSE, label = @Translation("File")),
 *     "taxonomy_term" = @ContextDefinition("entity:taxonomy_term", required = FALSE, label = @Translation("Term"))
 *   }
 * )
 */
class ContentEntityType extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['types'] = [
      '#title' => $this->t('Content Entity Types'),
      '#type' => 'checkboxes',
      '#options' => [
        'node' => $this->t('Node'),
        'media' => $this->t('Media'),
        'file' => $this->t('File'),
        'taxonomy_term' => $this->t('Taxonomy Term'),
      ],
      '#default_value' => $this->configuration['types'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['types'] = array_filter($form_state->getValue('types'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['types']) && !$this->isNegated()) {
      return TRUE;
    }

    foreach ($this->configuration['types'] as $type) {
      if ($this->getContext($type)->hasContextValue()) {
        $entity = $this->getContextValue($type);
        if ($entity && $entity->getEntityTypeId() == $type) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (count($this->configuration['types']) > 1) {
      $types = $this->configuration['types'];
      $last = array_pop($types);
      $types = implode(', ', $types);
      return $this->t(
        'The content entity is a @types or @last',
        [
          '@types' => $types,
          '@last' => $last,
        ]
      );
    }
    $type = reset($this->configuration['types']);
    return $this->t(
      'The content entity is a @type',
      [
        '@type' => $type,
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array_merge(
      ['types' => []],
      parent::defaultConfiguration()
    );
  }

}
