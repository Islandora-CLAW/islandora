<?php
/**
 * Created by PhpStorm.
 * User: whikloj
 * Date: 2017-02-28
 * Time: 9:57 PM
 */

namespace Drupal\islandora\Plugin\rest\resource;


use Drupal\islandora\FedoraResourceInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\Plugin\rest\resource\EntityResource;

/**
 * @RestResource(
 *   id = "fedora_resource",
 *   label = @Translation("Fedora Resource Jared"),
 *   uri_paths = {
 *     "canonical" = "/fedora_resource/{id}"
 *   }
 * )
 */
class FedoraResourceEntity extends ResourceBase {

 public function get($id = NULL) {
   if ($id) {
     dsm("GOT THE ID ({$id})");
   }
 }
}