<?php

namespace Drupal\islandora\Plugin\Condition;

/**
 * Provides a 'Term' condition for Media.
 *
 * @Condition(
 *   id = "media_has_term",
 *   label = @Translation("Media has term with URI"),
 *   context_definitions = {
 *     "media" = @ContextDefinition("entity:media", required = TRUE , label = @Translation("media"))
 *   }
 * )
 */
class MediaHasTerm extends NodeHasTerm {

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['uri']) && !$this->isNegated()) {
      return TRUE;
    }

    $media = $this->getContextValue('media');
    if (!$media) {
      return FALSE;
    }
    return $this->evaluateEntity($media);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (!empty($this->configuration['negate'])) {
      return $this->t('The media is not associated with taxonomy term with uri @uri.', ['@uri' => $this->configuration['uri']]);
    }
    else {
      return $this->t('The media is associated with taxonomy term with uri @uri.', ['@uri' => $this->configuration['uri']]);
    }
  }

}
