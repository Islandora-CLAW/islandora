<?php

/**
 * @file
 * Contains fedora_resource.page.inc.
 *
 * Page callback for Fedora resource entities.
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

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Fedora resource templates.
 *
 * Default template: fedora_resource.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_fedora_resource(array &$variables) {
  // Fetch FedoraResource Entity Object.
  $fedora_resource = $variables['elements']['#fedora_resource'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}

/**
 * Prepares variables for a custom entity type creation list templates.
 *
 * Default template: fedora_resource-content-add-list.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content: An array of fedora_resource-types.
 *
 * @see block_content_add_page()
 */
function template_preprocess_fedora_resource_content_add_list(&$variables) {
  $variables['types'] = array();
  $query = \Drupal::request()->query->all();
  foreach ($variables['content'] as $type) {
    $variables['types'][$type->id()] = array(
      'link' => Link::fromTextAndUrl($type->label(), new Url('entity.fedora_resource.add_form', array(
        'fedora_resource_type' => $type->id(),
      ), array('query' => $query))),
      'description' => array(
        '#markup' => $type->label(),
      ),
      'title' => $type->label(),
      'localized_options' => array(
        'query' => $query,
      ),
    );
  }
}
