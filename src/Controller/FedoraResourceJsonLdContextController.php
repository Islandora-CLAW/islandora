<?php

namespace Drupal\islandora\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class FedoraResourceJsonLdContextController.
 *
 * @package Drupal\islandora\Controller
 */
class FedoraResourceJsonLdContextController extends ControllerBase {

  /**
   * Displays JSON-LD Context for a fedora_resource bundle.
   */
  public function content($bundle, Request $request) {

    $context = \Drupal::service('islandora.jsonldcontextgenerator')->getContext('fedora_resource.' . $bundle);
    $build = array(
      '#type' => 'markup',
      '#prefix' => '<pre>',
      '#suffix' => '</pre>',
      '#markup' => $context,
    );
    return $build;
  }

}
