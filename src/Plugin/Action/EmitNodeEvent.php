<?php

namespace Drupal\islandora\Plugin\Action;

use Drupal\islandora\EventGenerator\EmitEvent;

/**
 * Emits a Node event.
 *
 * @Action(
 *   id = "emit_node_event",
 *   label = @Translation("Emit a node event to a queue/topic"),
 *   type = "node"
 * )
 */
class EmitNodeEvent extends EmitEvent {}
