<?php

namespace Drupal\islandora_breadcrumbs;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides breadcrumbs for nodes using a configured entity reference field.
 */
class IslandoraBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * The configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a breadcrumb builder.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('islandora.breadcrumbs');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $attributes) {
    // Using getRawParameters for consistency (always gives a string)
    // because getParameters sometimes returns a node and sometimes
    // returns a node object.
    $nid = $attributes->getRawParameters()->get('node');
    \Drupal::logger('test')->notice('Route Node ID: ' . print_r($nid, TRUE));
    if (!empty($nid)) {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      return (!empty($node) && $node->hasField($this->config->get('referenceField')) && !$node->get($this->config->get('referenceField'))->isEmpty());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {

    $node = $route_match->getParameter('node');
    $breadcrumb = new Breadcrumb();

    $chain = [];
    $this->walkMembership($node, $chain);

    if (!$this->config->get('includeSelf')) {
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

    if ($this->config->get('maxDepth') > 0 && count($crumbs) >= $this->config->get('maxDepth')) {
      return;
    }

    // Find the next in the chain, if there are any.
    if ($entity->hasField($this->config->get('referenceField')) &&
      !$entity->get($this->config->get('referenceField'))->isEmpty()) {
      $this->walkMembership($entity->get($this->config->get('referenceField'))->entity, $crumbs);
    }
  }

}
