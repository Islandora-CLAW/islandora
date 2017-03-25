<?php

namespace Drupal\islandora\Plugin\rest\resource;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\islandora\IslandoraConstants;
use Drupal\islandora\VersionCounter\VersionCounter;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\rest\resource\EntityResource;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Defines a FedoraResource Rest plugin.
 *
 * Class FedoraResourceEntity.
 *
 * @package Drupal\islandora\Plugin\rest\resource
 *
 * @RestResource(
 *   id = "fedora_resource",
 *   label = @Translation("Fedora Resource"),
 *   serialization_class = "Drupal\islandora\Entity\FedoraResource",
 *   deriver = "Drupal\rest\Plugin\Deriver\EntityDeriver",
 *   uri_paths = {
 *     "canonical" = "/fedora_resource/{id}",
 *     "https://www.drupal.org/link-relations/create" = "/entity/fedora_resource"
 *   }
 * )
 */
class FedoraResourceEntity extends EntityResource {

  /**
   * A database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a Drupal\rest\Plugin\rest\resource\EntityResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, array $serializer_formats, LoggerInterface $logger, ConfigFactoryInterface $config_factory, Connection $database, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $serializer_formats, $logger, $config_factory);
    $this->database = $database;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   *
   * But passing additional database Connection.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('config.factory'),
      $container->get('database'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function patch(EntityInterface $original_entity, EntityInterface $entity = NULL) {
    if ($entity == NULL) {
      throw new BadRequestHttpException('No entity content received.');
    }
    $definition = $this->getPluginDefinition();
    if ($entity->getEntityTypeId() != $definition['entity_type']) {
      throw new BadRequestHttpException('Invalid entity type');
    }
    if (!$original_entity->access('update')) {
      throw new AccessDeniedHttpException();
    }
    if (!$this->request->headers->has(IslandoraConstants::ISLANDORA_VCLOCK_HEADER)) {
      throw new BadRequestHttpException('No ' . IslandoraConstants::ISLANDORA_VCLOCK_HEADER . ' header provided.');
    }
    else {
      $uuid = $original_entity->uuid();
      $counter = new VersionCounter($this->database);
      $header = $this->request->headers->get(IslandoraConstants::ISLANDORA_VCLOCK_HEADER);
      if (is_array($header)) {
        $header = reset($header);
      }
      if ($counter->isValid($uuid, $header) === 0) {
        // Note: this message is not displayed.
        throw new ConflictHttpException(IslandoraConstants::ISLANDORA_VCLOCK_HEADER . ' value does not match.');
      }
    }

    // Overwrite the received properties.
    $entity_keys = $entity->getEntityType()->getKeys();
    foreach ($entity->_restSubmittedFields as $field_name) {
      try {
        $field = $entity->get($field_name);
      }
      catch (\InvalidArgumentException $e) {
        throw new BadRequestHttpException($e->getMessage(), $e);
      }

      // Entity key fields need special treatment: together they uniquely
      // identify the entity. Therefore it does not make sense to modify any of
      // them. However, rather than throwing an error, we just ignore them as
      // long as their specified values match their current values.
      if (in_array($field_name, $entity_keys, TRUE)) {
        // Unchanged values for entity keys don't need access checking.
        if ($original_entity->get($field_name)->getValue() === $entity->get($field_name)->getValue()) {
          continue;
        }
        // It is not possible to set the language to NULL as it is automatically
        // re-initialized. As it must not be empty, skip it if it is.
        elseif (isset($entity_keys['langcode']) && $field_name === $entity_keys['langcode'] && $field->isEmpty()) {
          continue;
        }
      }

      if (!$original_entity->get($field_name)->access('edit')) {
        throw new AccessDeniedHttpException("Access denied on updating field '$field_name'.");
      }
      $original_entity->set($field_name, $field->getValue());
    }

    // Validate the received data before saving.
    $this->validate($original_entity);
    try {
      $original_entity->save();
      $this->logger->notice('Updated entity %type with ID %id.', array('%type' => $original_entity->getEntityTypeId(), '%id' => $original_entity->id()));

      // Return the updated entity in the response body.
      return new ModifiedResourceResponse($original_entity, 200);
    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, 'Internal Server Error', $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method) {
    $route = parent::getBaseRoute($canonical_path, $method);
    // Just for PATCH for now.
    if (strtolower($method) == "patch") {
      $route->setDefault('_controller', 'Drupal\islandora\Plugin\rest\FedoraRestRequestHandler::handle');
    }
    return $route;
  }

}
