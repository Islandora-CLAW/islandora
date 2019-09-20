<?php

namespace Drupal\islandora\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Sets some view pages to use the admin theme.
 */
class AdminViewsRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('view.media_of.page_1')) {
      $route->setOption('_admin_route', 'TRUE');
      $route->setRequirement('_permission', 'manage media');
      $route->setRequirement('_custom_access', '\Drupal\islandora\Controller\ManageMediaController::access');
    }
    if ($route = $collection->get('view.manage_members.page_1')) {
      $route->setOption('_admin_route', 'TRUE');
      $route->setRequirement('_permission', 'manage members');
      $route->setRequirement('_custom_access', '\Drupal\islandora\Controller\ManageMediaController::access');
    }
    if ($route = $collection->get('view.reorder_children.page_1')) {
      $route->setOption('_admin_route', 'TRUE');
      $route->setRequirement('_permission', 'manage members');
    }
  }

}
