<?php

namespace Drupal\islandora\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Page to select new media type to add.
 */
class ManageMediaController extends ManageMembersController {

  /**
   * Renders a list of media types to add.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node you want to add a media to.
   *
   * @return array
   *   Array of media types to add.
   */
  public function addToNodePage(NodeInterface $node) {
    return $this->generateTypeList(
      'media',
      'media_type',
      'entity.media.add_form',
      'entity.media_type.add_form',
      $node,
      'field_media_of'
    );
  }

  public function access(AccountInterface $account, RouteMatch $route_match) {
    if ($account->hasPermission('manage media')) {
      if ($route_match->getParameters()->has('node')) {
        $node = $route_match->getParameter('node');
        if (! $node instanceof NodeInterface) {
          $node = Node::load($node);
        }
        if ($node->hasField('field_content_model') || $node->hasField('field_member_of')) {
          return AccessResult::allowed();
        }
      }
    }
    return AccessResult::forbidden();
  }

}
