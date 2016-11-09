<?php
/**
 * @file
 * Contains UuidEntityConverter.php
 *
 * This file is part of the Islandora Project.
 *
 * (c) Islandora Foundation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Diego Pino Navarro <dpino@metro.org> https://github.com/diegopino
 */

namespace Drupal\islandora\ParamConverter;


use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\Component\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

/**
 * Converts an UUID param (route) into an Entity.
 *
 * @ingroup islandora
 */
class UuidEntityConverter extends EntityConverter {

  /**
   * @inheritDoc
   */
  public function convert($value, $definition, $name, array $defaults) {
    return $this->entityManager->loadEntityByUuid($name, $value);
  }

  /**
   * @inheritDoc
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'uuid');
  }


}
