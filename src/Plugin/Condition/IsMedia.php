<?php

namespace Drupal\islandora\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\media_entity\MediaInterface;

/**
 * Provides an 'Is Media' condition.
 *
 * @Condition(
 *   id = "is_media",
 *   label = @Translation("Is Media"),
 *   context = {
 *     "media" = @ContextDefinition("entity:media", label = @Translation("Media"))
 *   }
 * )
 */
class IsMedia extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('The entity is a Media');
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    return $this->getContextValue('media') instanceof MediaInterface;
  }

}
