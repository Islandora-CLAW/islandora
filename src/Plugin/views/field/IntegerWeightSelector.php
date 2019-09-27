<?php

namespace Drupal\islandora\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;
use Drupal\views\Render\ViewsRenderPipelineMarkup;

/**
 * Field handler to present a weight selector element.
 *
 * A port of the weight module's weight selector element
 * to support an unsigned integer using the values found
 * in the result set.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("integer_weight_selector")
 */
class IntegerWeightSelector extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return ViewsRenderPipelineMarkup::create('<!--form-item-' . $this->options['id'] . '--' . $this->view->row_index . '-->');
  }

  /**
   * {@inheritdoc}
   */
  public function viewsForm(array &$form, FormStateInterface $form_state) {
    // The view is empty, abort.
    if (empty($this->view->result)) {
      return;
    }

    $form[$this->options['id']] = [
      '#tree' => TRUE,
    ];

    // Use the existing values of this result set to populate the options.
    $options = [];
    foreach ($this->view->result as $row_index => $row) {
      $options[$this->getValue($row)] = $this->getValue($row);
    }

    // If we were given some blank values we need to fill
    // out the option list from 1 through the result count
    // to make sure we have enough. (Blanks should only appear
    // at the beginning of the results list.)
    // Also, blank values will break the selector, remove it.
    if (array_key_exists('', $options)) {
      unset($options['']);
      for ($i = 1; $i <= $this->view->total_rows; $i++) {
        $options[$i] = $i;
      }
      ksort($options);
    }

    // Now that we have all the available weight values, populate the forms.
    foreach ($this->view->result as $row_index => $row) {
      $entity = $row->_entity;
      $field_langcode = $entity->getEntityTypeId() . '__' . $this->field . '_langcode';

      $form[$this->options['id']][$row_index]['weight'] = [
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => $this->getValue($row),
        '#attributes' => ['class' => ['weight-selector']],
      ];

      $form[$this->options['id']][$row_index]['entity'] = [
        '#type' => 'value',
        '#value' => $entity,
      ];

      $form[$this->options['id']][$row_index]['langcode'] = [
        '#type' => 'value',
        '#value' => $row->{$field_langcode},
      ];
    }

    $form['views_field'] = [
      '#type' => 'value',
      '#value' => $this->field,
    ];

    $form['#action'] = \Drupal::request()->getRequestUri();
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormSubmit(array &$form, FormStateInterface $form_state) {
    $field_name = $form_state->getValue('views_field');
    $rows = $form_state->getValue($field_name);

    foreach ($rows as $row) {
      if ($row['langcode']) {
        $entity = $row['entity']->getTranslation($row['langcode']);
      }
      else {
        $entity = $row['entity'];
      }
      if ($entity && $entity->hasField($field_name)) {
        $entity->set($field_name, $row['weight']);
        $entity->save();
      }
    }
  }

}
