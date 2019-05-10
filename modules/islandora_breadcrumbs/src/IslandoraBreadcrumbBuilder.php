<?php

namespace Drupal\islandora_breadcrumbs;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides a breadcrumb builder for nodes using field_member_of.
 */
class IslandoraBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $attributes) {
    $parameters = $attributes->getParameters()->all();
    if (!empty($parameters['node'])) {
      return ($parameters['node']->hasField('field_member_of') &&
              !$parameters['node']->field_member_of->isEmpty());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $node = $route_match->getParameter('node');
    $breadcrumb = new Breadcrumb();
    $chain = IslandoraBreadcrumbBuilder::walkMembership($node);

    // Don't include the current item. @TODO make configurable.
    array_pop($chain);
    $breadcrumb->addCacheableDependency($node);

    // Add membership chain to the breadcrumb.
    foreach ($chain as $chainlink) {
      $breadcrumb->addCacheableDependency($chainlink);
      $breadcrumb->addLink($chainlink->toLink());
    }
    $breadcrumb->addCacheContexts(['route']);
    return $breadcrumb;
  }

  /**
   * Follows chain of field_member_of links.
   */
  protected static function walkMembership(EntityInterface $entity) {
    if ($entity->hasField('field_member_of') &&
      !$entity->get('field_member_of')->isEmpty()) {
      $crumbs = IslandoraBreadcrumbBuilder::walkMembership($entity->get('field_member_of')->entity);
      $crumbs[] = $entity;
      return $crumbs;
    }
    return [$entity];
  }

}
