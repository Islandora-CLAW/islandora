<?php

namespace Drupal\islandora;

use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Fedora resource entities.
 *
 * @see Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 * @ingroup islandora
 */
class FedoraResourceHtmlRouteProvider extends AdminHtmlRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    if ($collection_route = $this->getCollectionRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.collection", $collection_route);
    }

    if ($add_form_route = $this->getAddFormRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.add_form", $add_form_route);
    }

    $add_page_route = $this->getAddPageRoute($entity_type);
    $collection->add("$entity_type_id.add_page", $add_page_route);

    if ($settings_form_route = $this->getSettingsFormRoute($entity_type)) {
      $collection->add("$entity_type_id.settings", $settings_form_route);
    }

    if ($uuid_route = $this->getUuidRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.uuid", $uuid_route);
    }

    return $collection;
  }

  /**
   * Gets the UUID route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getUuidRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->getKey('uuid') && $entity_type->hasViewBuilderClass() && $entity_type->hasLinkTemplate('uuid')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('uuid'));
      $route
        ->addDefaults([
          '_entity_view' => "{$entity_type_id}.full",
          '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::title',
        ])
        ->setRequirement('_entity_access', "{$entity_type_id}.view")
        ->setOption('parameters', [
          $entity_type_id => ['type' => "uuid"],
        ])
        // Fetch UUID pattern from Uuid class(constant)
        ->setRequirement($entity_type_id, '^' . Uuid::VALID_PATTERN . '$');
      return $route;
    }
  }

  /**
   * Gets the collection route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('collection') && $entity_type->hasListBuilderClass()) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('collection'));
      $route
        ->setDefaults([
          '_entity_list' => $entity_type_id,
          '_title' => "{$entity_type->getLabel()} list",
        ])
        ->setRequirement('_permission', 'view fedora resource entities')
        ->setOption('_admin_route', TRUE);

      return $route;
    }
  }

  /**
   * Gets the add-form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('add-form')) {
      $entity_type_id = $entity_type->id();
      $parameters = [
        $entity_type_id => ['type' => 'entity:' . $entity_type_id],
      ];

      $route = new Route($entity_type->getLinkTemplate('add-form'));
      $bundle_entity_type_id = $entity_type->getBundleEntityType();
      // Content entities with bundles are added via a dedicated controller.
      $route
        ->setDefaults([
          '_controller' => 'Drupal\islandora\Controller\FedoraResourceAddController::addForm',
          '_title_callback' => 'Drupal\islandora\Controller\FedoraResourceAddController::getAddFormTitle',
        ])
        ->setRequirement('_entity_create_access', $entity_type_id . ':{' . $bundle_entity_type_id . '}');
      $parameters[$bundle_entity_type_id] = ['type' => 'entity:' . $bundle_entity_type_id];

      $route
        ->setOption('parameters', $parameters)
        ->setOption('_admin_route', TRUE);

      return $route;
    }
  }

  /**
   * Gets the add page route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getAddPageRoute(EntityTypeInterface $entity_type) {
    $route = new Route("/admin/structure/{$entity_type->id()}/add");
    $route
      ->setDefaults([
        '_controller' => 'Drupal\islandora\Controller\FedoraResourceAddController::add',
        '_title' => "Add {$entity_type->getLabel()}",
      ])
      ->setRequirement('_entity_create_access', $entity_type->id())
      ->setOption('_admin_route', TRUE);

    return $route;
  }

  /**
   * Gets the settings form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getSettingsFormRoute(EntityTypeInterface $entity_type) {
    if (!$entity_type->getBundleEntityType()) {
      $route = new Route("/admin/structure/{$entity_type->id()}/settings");
      $route
        ->setDefaults([
          '_form' => 'Drupal\islandora\Form\FedoraResourceSettingsForm',
          '_title' => "{$entity_type->getLabel()} settings",
        ])
        ->setRequirement('_permission', $entity_type->getAdminPermission())
        ->setOption('_admin_route', TRUE);

      return $route;
    }
  }

}
