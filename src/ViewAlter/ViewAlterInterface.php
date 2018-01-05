<?php

namespace Drupal\islandora\ViewAlter;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for a service that performs a view alter.
 */
interface ViewAlterInterface {

  /**
   * Edits a build array, 'atlering' its view.
   *
   * @param array $build
   *   The build array.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity being viewed.
   */
  public function alter(array &$build, EntityInterface $entity);

}
