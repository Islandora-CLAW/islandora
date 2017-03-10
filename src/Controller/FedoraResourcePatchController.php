<?php

namespace Drupal\islandora\Controller;


use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

class FedoraResourcePatchController extends ControllerBase {



  public function process(Request $request) {
    error_log("GOT SOMETHING");
    dsm($request, 'Request');
  }
}