<?php

namespace Drupal\islandora\Plugin\ContextReaction;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\islandora\ContextReaction\NormalizerAlterReaction;
use Drupal\jsonld\Normalizer\NormalizerBase;

/**
 * Map URI to predicate context reaction.
 *
 * @ContextReaction(
 *   id = "map_uri_predicate",
 *   label = @Translation("Map URI to predicate")
 * )
 */
class MappingUriPredicateReaction extends NormalizerAlterReaction {

  const URI_PREDICATE = 'drupal_uri_predicate';

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Map Drupal URI to configured predicate.');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(EntityInterface $entity = NULL, array &$normalized = NULL, array $context = NULL) {
    $config = $this->getConfiguration();
    $drupal_predicate = $config[self::URI_PREDICATE];
    if (!is_null($drupal_predicate) && !empty($drupal_predicate)) {
      $url = $entity
        ->toUrl('canonical', ['absolute' => TRUE])
        ->setRouteParameter('_format', 'jsonld')
        ->toString();
      if ($context['needs_jsonldcontext'] === FALSE) {
        $drupal_predicate = NormalizerBase::escapePrefix($drupal_predicate, $context['namespaces']);
      }
      if (isset($normalized['@graph']) && is_array($normalized['@graph'])) {
        foreach ($normalized['@graph'] as &$graph) {
          if (isset($graph['@id']) && $graph['@id'] == $url) {
            if (isset($graph[$drupal_predicate])) {
              if (!is_array($graph[$drupal_predicate])) {
                if ($graph[$drupal_predicate] == $url) {
                  // Don't add it if it already exists.
                  return;
                }
                $tmp = $graph[$drupal_predicate];
                $graph[$drupal_predicate] = [$tmp];
              }
              elseif (array_search($url, array_column($graph[$drupal_predicate], '@value'))) {
                // Don't add it if it already exists.
                return;
              }
            }
            else {
              $graph[$drupal_predicate] = [];
            }
            $graph[$drupal_predicate][] = ["@value" => $url];
            return;
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $form[self::URI_PREDICATE] = [
      '#type' => 'textfield',
      '#title' => $this->t('Drupal URI predicate'),
      '#description' => $this->t("The Drupal object's URI will be added to the resource with this predicate. Must use a defined prefix."),
      '#default_value' => isset($config[self::URI_PREDICATE]) ? $config[self::URI_PREDICATE] : '',
      '#size' => 35,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $drupal_predicate = $form_state->getValue(self::URI_PREDICATE);
    if (!is_null($drupal_predicate) and !empty($drupal_predicate)) {
      if (preg_match('/^https?:\/\//', $drupal_predicate)) {
        // Can't validate all URIs so we have to trust them.
        return;
      }
      elseif (preg_match('/^([^\s:]+):/', $drupal_predicate, $matches)) {
        $predicate_prefix = $matches[1];
        $rdf = rdf_get_namespaces();
        $rdf_prefixes = array_keys($rdf);
        if (!in_array($predicate_prefix, $rdf_prefixes)) {
          $form_state->setErrorByName(
            self::URI_PREDICATE,
            $this->t('Namespace prefix @prefix is not registered.',
              ['@prefix' => $predicate_prefix]
            )
          );
        }
      }
      else {
        $form_state->setErrorByName(
          self::URI_PREDICATE,
          $this->t('Predicate must use a defined prefix or be a full URI')
        );
      }
    }
    parent::validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration([self::URI_PREDICATE => $form_state->getValue(self::URI_PREDICATE)]);
  }

}
