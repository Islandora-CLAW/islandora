<?php

namespace Drupal\islandora\Plugin\ContextReaction;

use Drupal\islandora\PresetReaction\PresetReaction;

/**
 * Delete context reaction.
 *
 * @ContextReaction(
 *   id = "delete",
 *   label = @Translation("Delete")
 * )
 */
class DeleteReaction extends PresetReaction {}
