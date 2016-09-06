<?php

namespace Drupal\islandora;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Fedora resource entity.
 *
 * @see \Drupal\islandora\Entity\FedoraResource.
 * @ingroup islandora
 */
class FedoraResourceAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\islandora\FedoraResourceInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished fedora resource entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published fedora resource entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit fedora resource entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete fedora resource entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add fedora resource entities');
  }

}
