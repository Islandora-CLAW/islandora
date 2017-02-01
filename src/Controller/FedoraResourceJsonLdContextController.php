<?php

namespace Drupal\islandora\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\system\Tests\DrupalKernel\ContentNegotiationTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class FedoraResourceJsonLdContextController.
 *
 * @package Drupal\islandora\Controller
 */
class FedoraResourceJsonLdContextController extends ControllerBase {

    public function content($bundle, Request $request) {

        $context = \Drupal::service('islandora.jsonldcontextgenerator')->getContext('fedora_resource.'.$bundle);
        $build = array(
            '#type' => 'markup',
            '#prefix' => '<pre>',
            '#suffix' => '</pre>',
            '#markup' => $context,
        );
        return $build;
    }

}
