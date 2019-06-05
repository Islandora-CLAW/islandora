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

  protected $depthLimit = 2;
  protected $includeSelf = FALSE;

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

    $config = \Drupal::config('islandora.breadcrumbs');
    if (!empty($config->get('depthLimit'))) {
      $this->depthLimit = $config->get('depthLimit');
    }
    if (!empty($config->get('includeSelf'))) {
      $this->includeSelf = $config->get('includeSelf');
    }

    $node = $route_match->getParameter('node');
    $breadcrumb = new Breadcrumb();

    $chain = [];
    $this->walkMembership($node, $chain);

    if (!$this->includeSelf) {
      array_pop($chain);
    }
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
   *
   * We pass crumbs by reference to enable checking for looped chains.
   */
  protected function walkMembership(EntityInterface $entity, &$crumbs) {
    // Avoid infinate loops, return if we've seen this before.
    foreach ($crumbs as $crumb) {
      if ($crumb->uuid == $entity->uuid) {
        return;
      }
    }

    // Add this item onto the pile.
    array_unshift($crumbs, $entity);

    if ($this->depthLimit > 0 && count($crumbs) >= $this->depthLimit) {
      return;
    }

    // Find the next in the chain, if there are any.
    if ($entity->hasField('field_member_of') &&
      !$entity->get('field_member_of')->isEmpty()) {
      $this->walkMembership($entity->get('field_member_of')->entity, $crumbs);
    }
  }

}
