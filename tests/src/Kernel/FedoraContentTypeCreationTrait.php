<?php

namespace Drupal\Tests\islandora\Kernel;

use Drupal\Component\Utility\Random;
use Drupal\islandora\Entity\FedoraResourceType;

/**
 * Trait that aids in the creation of a fedora resource type bundle.
 */
trait FedoraContentTypeCreationTrait {

  /**
   * Creates a custom content Fedora Resource type based on default settings.
   *
   * @param array $values
   *   An array of settings to change from the defaults.
   *   Example: 'id' => 'some_bundle'.
   *
   * @return \Drupal\islandora\Entity\FedoraResourceType
   *   Created content type.
   */
  protected function createFedoraResourceContentType(array $values = []) {
    // Find a non-existent random type name.
    $random = new Random();
    if (!isset($values['type'])) {
      do {
        $id = strtolower($random->string(8));
      } while (FedoraResourceType::load($id));
    }
    else {
      $id = $values['type'];
    }
    $values += [
      'id' => $id,
      'label' => $id,
    ];
    $type = FedoraResourceType::create($values);
    $type->save();
    return $type;
  }

}
