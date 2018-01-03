<?php

namespace Drupal\islandora\Plugin\Action;

use Drupal\islandora\EventGenerator\EmitEvent;

/**
 * Emits a Media event.
 *
 * @Action(
 *   id = "emit_media_event",
 *   label = @Translation("Emit a media event to a queue/topic"),
 *   type = "media"
 * )
 */
class EmitMediaEvent extends EmitEvent {}
