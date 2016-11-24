<?php

namespace Drupal\islandora\ParamConverter;

use Drupal\Core\ParamConverter\EntityConverter;
use Symfony\Component\Routing\Route;

/**
 * Converts an UUID param (route) into an Entity.
 *
 * @ingroup islandora
 */
class UuidEntityConverter extends EntityConverter {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    return $this->entityManager->loadEntityByUuid($name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'uuid');
  }

}
