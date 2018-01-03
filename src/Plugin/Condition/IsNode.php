<?php

namespace Drupal\islandora\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\node\NodeInterface;

/**
 * Provides an 'Is Node' condition.
 *
 * @Condition(
 *   id = "is_node",
 *   label = @Translation("Is Node"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
 * )
 */
class IsNode extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('The entity is a Node');
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    return $this->getContextValue('node') instanceof NodeInterface;
  }

}
