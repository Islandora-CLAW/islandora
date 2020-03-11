<?php

namespace Drupal\islandora\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatch;
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
    // The role fedoraAdmin is currently hardcoded and
    // must be in the user's profile for successful writes to Fedora.
    $roles = $this->currentUser->getRoles();
    $checked_fields = ['file', 'image'];
    $list = $this->generateTypeList(
      'media',
      'media_type',
      'entity.media.add_form',
      'entity.media_type.add_form',
      $node,
      'field_media_of'
    );
    if (!in_array('fedoraadmin', $roles)) {
      $bundles = $list['#bundles'];
      foreach ($bundles as $label => $bundle) {
        $fields = $this->entityFieldManager->getFieldDefinitions('media', $label);
        foreach ($fields as $field) {
          $file_type = $field->getType();
          if (in_array($file_type, $checked_fields)) {
            $scheme = $field->getSetting('uri_scheme');
            if ($scheme == 'fedora') {
              unset($list['#bundles'][$label]);
            }
          }
        }
      }
    }
    return $list;
  }

  /**
   * Check if the object being displayed "is Islandora".
   *
   * @param \Drupal\Core\Routing\RouteMatch $route_match
   *   The current routing match.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   *   Whether we can or can't show the "thing".
   */
  public function access(RouteMatch $route_match) {
    if ($route_match->getParameters()->has('node')) {
      $node = $route_match->getParameter('node');
      if (!$node instanceof NodeInterface) {
        $node = Node::load($node);
      }
      if ($node->hasField('field_model') && $node->hasField('field_member_of')) {
        return AccessResult::allowed();
      }
    }
    return AccessResult::forbidden();
  }

}
