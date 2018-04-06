<?php

namespace Drupal\islandora;

use Drupal\context\ContextManager;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\file\FileInterface;
use Drupal\islandora\ContextProvider\NodeContextProvider;
use Drupal\islandora\ContextProvider\MediaContextProvider;
use Drupal\islandora\ContextProvider\FileContextProvider;
use Drupal\media_entity\MediaInterface;
use Drupal\node\NodeInterface;

/**
 * Utility functions for figuring out when to fire derivative reactions.
 */
class DerivativeUtils {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Entity query.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Context manager.
   *
   * @var \Drupal\context\ContextManager
   */
  protected $contextManager;

  /**
   * Stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManager
   */
  protected $streamWrapperManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   Entity query.
   * @param \Drupal\context\ContextManager $context_manager
   *   Context manager.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManager $stream_wrapper_manager
   *   Stream wrapper manager.
   */
  public function __construct(
    EntityTypeManager $entity_type_manager,
    EntityFieldManager $entity_field_manager,
    QueryFactory $entity_query,
    ContextManager $context_manager,
    StreamWrapperManager $stream_wrapper_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityQuery = $entity_query;
    $this->contextManager = $context_manager;
    $this->streamWrapperManager = $stream_wrapper_manager;
  }

  /**
   * Determines if a field is meant to reference media.
   *
   * @param string $entity_type
   *   Type of entity that has the field.
   * @param string $bundle
   *   Bundle that has the field.
   * @param string $field
   *   Field name.
   *
   * @return bool
   *   TRUE if the field is a media reference field.
   */
  public function isMediaReferenceField($entity_type, $bundle, $field) {
    $field_config = $this->entityTypeManager->getStorage('field_config')->load("$entity_type.$bundle.$field");
    if (!$field_config) {
      return FALSE;
    }
    $storage_def = $field_config->getFieldStorageDefinition();
    return $storage_def->isBaseField() == FALSE &&
      $field_config->getType() == "entity_reference" &&
      $storage_def->getSetting('target_type') == 'media';
  }

  /**
   * Retrieves field storage definitions for an Entity that references Media.
   *
   * @param string $entity_type
   *   Type of content entity (e.g. node, media, etc...) doing the referencing.
   * @param string $bundle
   *   Bundle of content entity doing the referencing.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   Associative array of FieldDefinitionInterfaces keyed by field name.
   */
  public function getMediaReferenceFields($entity_type, $bundle) {
    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
    return array_filter($fields, function (FieldDefinitionInterface $field_def) use ($entity_type, $bundle) {
      return $this->isMediaReferenceField($entity_type, $bundle, $field_def->getName());
    });
  }

  /**
   * Retrieves Media that have just been added to an entity reference field.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Node suspected of referencing a Media.
   *
   * @return \Drupal\media_entity\MediaInterface[]
   *   Array of MediaInterfaces keyed by id.
   */
  public function getNewMediaReferences(NodeInterface $node) {
    $media_reference_fields = $this->getMediaReferenceFields($node->getEntityTypeId(), $node->bundle());

    $mids = [];
    foreach ($media_reference_fields as $field_name => $field_definition) {
      $field_item_list = $node->get($field_name);
      $original_field_item_list = $node->original->get($field_name);

      // Continue if empty or no change in entity reference field.
      if ($field_item_list->isempty() || $field_item_list->equals($original_field_item_list)) {
        continue;
      }

      // Check each entity that's referenced in each field by searching the
      // original values before the update occurred.  If it's found, the media
      // is not newly referenced.
      foreach ($field_item_list as $field_item) {
        // Grab the mid and search the original list.
        $value = $field_item->getvalue();
        $mid = $value['target_id'];

        $is_found = FALSE;
        foreach ($original_field_item_list as $item) {
          $orig_value = $item->getvalue();
          $orig_mid = $orig_value['target_id'];
          if ($is_found = $orig_mid == $mid) {
            break;
          }
        }

        if ($is_found) {
          continue;
        }

        $mids[] = $mid;
      }
    }

    return empty($mids) ? [] : $this->entityTypeManager->getStorage('media')->loadMultiple($mids);
  }

  /**
   * Indicates if a Media is referenced by a Node.
   *
   * @param int $mid
   *   Id of Media whose referencers you are searching for.
   *
   * @return bool
   *   TRUE if Media is referenced by any Node.
   */
  public function mediaIsReferenced($mid) {
    return !empty($this->getReferencingNodeIds($mid));
  }

  /**
   * Gets  Nodes that reference a Media.
   *
   * @param int $mid
   *   Id of Media whose referencing Nodes you are searching for.
   *
   * @return \Drupal\node\NodeInterface[]
   *   Array of nodes.
   */
  public function getReferencingNodes($mid) {
    return $this->entityTypeManager->getStorage('node')->loadMultiple(
      $this->getReferencingNodeIds($mid)
    );
  }

  /**
   * Gets ids for Nodes that reference a Media.
   *
   * @param int $mid
   *   Id of Media whose referencing Nodes you are searching for.
   *
   * @return int[]
   *   Array of node ids.
   */
  public function getReferencingNodeIds($mid) {
    // Get all node fields that are entity references to Media.
    $fields = $this->entityQuery->get('field_storage_config')
      ->condition('entity_type', 'node')
      ->condition('type', 'entity_reference')
      ->condition('settings.target_type', 'media')
      ->execute();

    // Process field names, stripping off 'node.' and appending 'target_id'.
    $conditions = array_map(
      function ($field) {
        return ltrim($field, 'node.') . '.target_id';
      },
      $fields
    );

    // Query for nodes that reference this media.
    $query = $this->entityQuery->get('node', 'OR');
    foreach ($conditions as $condition) {
      $query->condition($condition, $mid);
    }
    return $query->execute();
  }

  /**
   * Gets ids for Media that reference a File.
   *
   * @param int $fid
   *   File id.
   *
   * @return array
   *   Array of media ids
   */
  public function getReferencingMediaIds($fid) {
    // Get media fields that reference files.
    $fields = $this->entityQuery->get('field_storage_config')
      ->condition('entity_type', 'media')
      ->condition('settings.target_type', 'file')
      ->execute();

    // Process field names, stripping off 'media.' and appending 'target_id'.
    $conditions = array_map(
      function ($field) {
        return ltrim($field, 'media.') . '.target_id';
      },
      $fields
    );

    // Query for media that reference this file.
    $query = $this->entityQuery->get('media', 'OR');
    foreach ($conditions as $condition) {
      $query->condition($condition, $fid);
    }

    return $query->execute();
  }

  /**
   * Executes context reactions for a Node.
   *
   * @param string $reaction_type
   *   Reaction type.
   * @param \Drupal\node\NodeInterface $node
   *   Node to evaluate contexts and pass to reaction.
   */
  public function executeNodeReactions($reaction_type, NodeInterface $node) {
    $provider = new NodeContextProvider($node);
    $provided = $provider->getRuntimeContexts([]);
    $this->contextManager->evaluateContexts($provided);

    // Fire off index reactions.
    foreach ($this->contextManager->getActiveReactions($reaction_type) as $reaction) {
      $reaction->execute($node);
    }
  }

  /**
   * Executes context reactions for a Media.
   *
   * @param string $reaction_type
   *   Reaction type.
   * @param \Drupal\media_entity\MediaInterface $media
   *   Media to evaluate contexts and pass to reaction.
   */
  public function executeMediaReactions($reaction_type, MediaInterface $media) {
    $provider = new MediaContextProvider($media);
    $provided = $provider->getRuntimeContexts([]);
    $this->contextManager->evaluateContexts($provided);

    // Fire off index reactions.
    foreach ($this->contextManager->getActiveReactions($reaction_type) as $reaction) {
      $reaction->execute($media);
    }
  }

  /**
   * Executes context reactions for a File.
   *
   * @param string $reaction_type
   *   Reaction type.
   * @param \Drupal\file\FileInterface $file
   *   File to evaluate contexts and pass to reaction.
   */
  public function executeFileReactions($reaction_type, FileInterface $file) {
    $provider = new FileContextProvider($file);
    $provided = $provider->getRuntimeContexts([]);
    $this->contextManager->evaluateContexts($provided);

    // Fire off index reactions.
    foreach ($this->contextManager->getActiveReactions($reaction_type) as $reaction) {
      $reaction->execute($file);
    }
  }

  /**
   * Executes derivative reactions for a Media and Node.
   *
   * @param string $reaction_type
   *   Reaction type.
   * @param \Drupal\node\NodeInterface $node
   *   Node to pass to reaction.
   * @param \Drupal\media_entity\MediaInterface $media
   *   Media to evaluate contexts.
   */
  public function executeDerivativeReactions($reaction_type, NodeInterface $node, MediaInterface $media) {
    $provider = new MediaContextProvider($media);
    $provided = $provider->getRuntimeContexts([]);
    $this->contextManager->evaluateContexts($provided);

    // Fire off index reactions.
    foreach ($this->contextManager->getActiveReactions($reaction_type) as $reaction) {
      $reaction->execute($node);
    }
  }

  /**
   * Evaluates if fields have changed between two instances of a ContentEntity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The updated entity.
   * @param \Drupal\Core\Entity\ContentEntityInterface $original
   *   The original entity.
   */
  public function haveFieldsChanged(ContentEntityInterface $entity, ContentEntityInterface $original) {

    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());

    $ignore_list = ['vid' => 1, 'changed' => 1, 'path' => 1];
    $field_definitions = array_diff_key($field_definitions, $ignore_list);

    foreach ($field_definitions as $field_name => $field_definition) {
      $langcodes = array_keys($entity->getTranslationLanguages());

      if ($langcodes !== array_keys($original->getTranslationLanguages())) {
        // If the list of langcodes has changed, we need to save.
        return TRUE;
      }

      foreach ($langcodes as $langcode) {
        $items = $entity
          ->getTranslation($langcode)
          ->get($field_name)
          ->filterEmptyItems();
        $original_items = $original
          ->getTranslation($langcode)
          ->get($field_name)
          ->filterEmptyItems();

        // If the field items are not equal, we need to save.
        if (!$items->equals($original_items)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

}
