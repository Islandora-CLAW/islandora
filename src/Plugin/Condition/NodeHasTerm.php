<?php

namespace Drupal\islandora\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\islandora\IslandoraUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Term' condition for nodes.
 *
 * @Condition(
 *   id = "node_has_term",
 *   label = @Translation("Node has term"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node", required = TRUE , label = @Translation("node"))
 *   }
 * )
 */
class NodeHasTerm extends ConditionPluginBase implements ContainerFactoryPluginInterface {

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
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $default = [];
    if (isset($this->configuration['uri']) && !empty($this->configuration['uri'])) {
      $uris = explode(',', $this->configuration['uri']);
      foreach ($uris as $uri) {
        $default[] = $this->utils->getTermForUri($uri);
      }
    }

    $form['term'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Term'),
      '#tags' => TRUE,
      '#default_value' => $default,
      '#target_type' => 'taxonomy_term',
      '#required' => TRUE,
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Set URI for term if possible.
    $this->configuration['uri'] = NULL;
    $value = $form_state->getValue('term');
    $uris = [];
    if (!empty($value)) {
      foreach ($value as $target) {
        $tid = $target['target_id'];
        $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
        $uri = $this->utils->getUriForTerm($term);
        if ($uri) {
          $uris[] = $uri;
        }
      }
      if (!empty($uris)) {
        $this->configuration['uri'] = implode(',', $uris);
      }
    }
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['uri']) && !$this->isNegated()) {
      return TRUE;
    }

    $node = $this->getContextValue('node');
    if (!$node) {
      return FALSE;
    }
    return $this->evaluateEntity($node);
  }

  /**
   * Evaluates if an entity has the specified term(s).
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to evalute.
   *
   * @return bool
   *   TRUE if entity has all the specified term(s), otherwise FALSE.
   */
  protected function evaluateEntity(EntityInterface $entity) {
    // Find the terms on the node.
    $terms = array_filter($entity->referencedEntities(), function ($entity) {
      return $entity->getEntityTypeId() == 'taxonomy_term' &&
         $entity->hasField(IslandoraUtils::EXTERNAL_URI_FIELD) &&
         !$entity->get(IslandoraUtils::EXTERNAL_URI_FIELD)->isEmpty();
    });

    // Get their URIs.
    $haystack = array_map(function ($term) {
        return $term->get(IslandoraUtils::EXTERNAL_URI_FIELD)->first()->getValue()['uri'];
    },
      $terms
    );

    // FALSE if there's no URIs on the node.
    if (empty($haystack)) {
      return $this->isNegated() ? TRUE : FALSE;
    }

    // Get the URIs to look for.  It's a required field, so there
    // will always be one.
    $needles = explode(',', $this->configuration['uri']);

    // TRUE if every needle is in the haystack.
    if (count(array_intersect($needles, $haystack)) == count($needles)) {
      return $this->isNegated() ? FALSE : TRUE;
    }

    // Otherwise, FALSE.
    return $this->isNegated() ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (!empty($this->configuration['negate'])) {
      return $this->t('The node is not associated with taxonomy term with uri @uri.', ['@uri' => $this->configuration['uri']]);
    }
    else {
      return $this->t('The node is associated with taxonomy term with uri @uri.', ['@uri' => $this->configuration['uri']]);
    }
  }

}
