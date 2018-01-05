<?php

namespace Drupal\islandora\ViewAlter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Url;

/**
 * Adds rel="describes" and rel="edit-media" link headers for files media.
 */
class MediaLinkHeaders extends LinkHeaderAlter implements ViewAlterInterface {

  /**
   * Media bundle storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mediaBundleStorage;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $media_bundle_storage
   *   Media bundle storage.
   */
  public function __construct(EntityStorageInterface $media_bundle_storage) {
    $this->mediaBundleStorage = $media_bundle_storage;
  }

  /**
   * Static factory.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   */
  public static function create(EntityTypeManager $entity_type_manager) {
    return new static(
      $entity_type_manager->getStorage('media_bundle')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function alter(array &$build, EntityInterface $entity) {
    $media_bundle = $this->mediaBundleStorage->load($entity->bundle());

    $type_configuration = $media_bundle->getTypeConfiguration();

    if (!isset($type_configuration['source_field'])) {
      return;
    }

    $source_field = $type_configuration['source_field'];

    if (empty($source_field) ||
        !$entity instanceof FieldableEntityInterface ||
        !$entity->hasField($source_field)
    ) {
      return;
    }

    // Collect file links for the media.
    $links = [];
    foreach ($entity->get($source_field)->referencedEntities() as $referencedEntity) {
      if ($entity->access('view')) {
        $file_url = $referencedEntity->url('canonical', ['absolute' => TRUE]);
        $edit_media_url = Url::fromRoute('rest.entity.file.GET.json', ['file' => $referencedEntity->id()])
          ->setAbsolute()
          ->toString();
        $edit_media_url .= '?_format=json';
        $links[] = "<$file_url>; rel=\"describes\"; type=\"{$referencedEntity->getMimeType()}\"";
        $links[] = "<$edit_media_url>; rel=\"edit-media\"; type=\"application/json\"";
      }
    }

    // Exit early if there aren't any.
    if (empty($links)) {
      return;
    }

    // Assemble and add as 'Link' header if it already exists.
    $header_str = implode(", ", $links);
    $this->addLinkHeaders($build, $header_str);
  }

}
