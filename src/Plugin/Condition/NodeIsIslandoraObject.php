<?php

namespace Drupal\islandora\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\islandora\IslandoraUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks whether node has fields that qualify it as an "Islandora" node.
 *
 * @Condition(
 *   id = "node_is_islandora_object",
 *   label = @Translation("Node is an Islandora object"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node", required = TRUE , label = @Translation("node"))
 *   }
 * )
 */
class NodeIsIslandoraObject extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Islandora utils.
   *
   * @var \Drupal\islandora\IslandoraUtils
   */
  protected $utils;

  /**
   * Term storage.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\islandora\IslandoraUtils $utils
   *   Islandora utils.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    IslandoraUtils $utils,
    EntityTypeManager $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->utils = $utils;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('islandora.utils'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $node = $this->getContextValue('node');
    if (!$node) {
      return FALSE;
    }
    // Islandora objects have these two fields.
    if ($node->hasField('field_model') && $node->hasField('field_member_of')) {
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (!empty($this->configuration['negate'])) {
      return $this->t('The node is not an Islandora object.');
    }
    else {
      return $this->t('The node is an Islandora object.');
    }
  }

}
