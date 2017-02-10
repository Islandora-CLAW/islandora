<?php

namespace Drupal\islandora\RdfBundleSolver;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\rdf\RdfMappingInterface;
use Drupal\rdf\Entity\RdfMapping;
use Psr\Log\LoggerInterface;

/**
 * A reliable JSON-LD @Context generation class.
 *
 * Class JsonldContextGenerator.
 *
 * @package Drupal\islandora\RdfBundleSolver
 */
class JsonldContextGenerator implements JsonldContextGeneratorInterface {

  /**
   * Constant Naming convention used to prefix name cache bins($cid)
   */
  const CACHE_BASE_CID = 'islandora:jsonld:context';


  /**
   * Injected EntityFieldManager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager = NULL;

  /**
   * Injected EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager = NULL;

  /**
   * Injected EntityTypeBundle.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo = NULL;

  /**
   * Injected Cache implementation.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Injected Logger Interface.
   *
   * @var \Psr\Log\LoggerInterface
   *   A logger instance.
   */
  protected $logger;

  /**
   * Constructs a JsonldContextGenerator object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The language manager.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, EntityTypeBundleInfoInterface $bundle_info, EntityTypeManagerInterface $entity_manager, CacheBackendInterface $cache_backend, LoggerInterface $logger_channel) {
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_manager;
    $this->bundleInfo = $bundle_info;
    $this->cache = $cache_backend;
    $this->logger = $logger_channel;
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($ids = 'fedora_resource.rdf_source') {
    $cid = JsonldContextGenerator::CACHE_BASE_CID . $ids;
    $cache = $this->cache->get($cid);
    $data = '';
    if (!$cache) {
      $rdfMapping = RdfMapping::load($ids);
      // Our whole chain of exceptions will never happen
      // because RdfMapping:load returns NULL on non existance
      // Which forces me to check for it
      // and don't even call writeCache on missing
      // Solution, throw also one here.
      if ($rdfMapping) {
        $data = $this->writeCache($rdfMapping, $cid);
      }
      else {
        $msg = t("Can't generate JSON-LD Context for @ids without RDF Mapping present.",
          array('@ids' => $ids));
        $this->logger->warning("@msg",
          array(
            '@msg' => $msg,
          ));
        throw new \Exception($msg);
      }
    }
    else {
      $data = $cache->data;
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function generateContext(RdfMappingInterface $rdfMapping) {
    // TODO: we will need to use \Drupal\Core\Fieldt\FieldDefinitionInterface
    // a lot to be able to create/frame/discern drupal bundles based on JSON-LD
    // So keep an eye on that definition.
    $allRdfNameSpaces = rdf_get_namespaces();

    // This one will become our return value.
    $jsonLdContextArray['@context'] = [];

    // Temporary array to keep track of our used namespaces and props.
    $theAccumulator = [];

    $bundle_rdf_mappings = $rdfMapping->getPreparedBundleMapping();
    $drupal_types = $this->entityBundleIdsSplitter($rdfMapping->id());
    $entity_type_id = $drupal_types['entityTypeId'];
    $bundle = $drupal_types['bundleId'];
    // If we don't have rdf:type(s) for this bundle then it makes little
    // sense to continue.
    // This only generates an Exception if there is an
    // rdfmapping object but has no rdf:type.
    if (empty($bundle_rdf_mappings['types'])) {
      $msg = t("Can't generate JSON-LD Context without at least one rdf:type for Entity type @entity_type, Bundle @bundle_name combo.",
        array('@entity_type' => $entity_type_id, ' @bundle_name' => $bundle));
      $this->logger->warning("@msg",
        array(
          '@msg' => $msg,
        ));
      throw new \Exception($msg);
    }

    /* We have a lot of assumptions here (rdf module is strange)
    a) xsd and other utility namespaces are in place
    b) the user knows what/how rdf mapping works and does it right
    c) that if a field's mapping_type is "rel" or "rev" and datatype is
    not defined, then '@type' is uncertain.
    d) that mapping back and forward is 1 to 1.
    Drupal allows multiple fields to be mapped to a same rdf prop
    but that does not scale back. If drupal gets an input with a list
    of values for a given property, we would never know in which Drupal
    fields we should put those values. it's the many to one,
    one to many reduction problem made worst by the abstraction of
    fields being containers of mappings and not rdf properties. */
    // Only care for those mappings that point to bundled or base fields.
    // First our bundled fields.
    foreach ($this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle) as $bundleFieldName => $fieldDefinition) {
      $field_context = $this->getFieldsRdf($rdfMapping, $bundleFieldName, $fieldDefinition, $allRdfNameSpaces);
      $theAccumulator = array_merge($field_context, $theAccumulator);
    }
    // And then our Base fields.
    foreach ($this->entityFieldManager->getBaseFieldDefinitions($entity_type_id) as $baseFieldName => $fieldDefinition) {
      $field_context = $this->getFieldsRdf($rdfMapping, $baseFieldName, $fieldDefinition, $allRdfNameSpaces);
      $theAccumulator = array_merge($field_context, $theAccumulator);
    }
    $theAccumulator = array_filter($theAccumulator);
    $jsonLdContextArray['@context'] = $theAccumulator;
    return json_encode($jsonLdContextArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  }

  /**
   * Gets the correct piece of @context for a given entity field.
   *
   * @param RdfMappingInterface $rdfMapping
   *    Rdf mapping object.
   * @param string $field_name
   *    The name of the field.
   * @param FieldDefinitionInterface $fieldDefinition
   *    The definition of the field.
   * @param array $allRdfNameSpaces
   *    Every RDF prefixed namespace in this Drupal.
   *
   * @return array
   *    Piece of JSON-LD context that supports this field
   */
  private function getFieldsRdf(RdfMappingInterface $rdfMapping, $field_name, FieldDefinitionInterface $fieldDefinition, array $allRdfNameSpaces) {
    $termDefinition = array();
    $fieldContextFragment = array();
    $fieldRDFMapping = $rdfMapping->getPreparedFieldMapping($field_name);
    if (!empty($fieldRDFMapping)) {
      // If one ore more properties, all will share same datatype so
      // get that before iterating.
      // First get our defaults, no-user or config based input.
      $default_field_term_mapping = $this->getTermContextFromField($fieldDefinition->getType());

      // Now we start overriding from config entity defined mappings.
      // Assume all non defined mapping types as "property".
      $reltype = isset($fieldRDFMapping['mapping_type']) ? $fieldRDFMapping['mapping_type'] : 'property';

      if (isset($fieldRDFMapping['datatype']) && ($reltype == 'property')) {
        $termDefinition = array('@type' => $fieldRDFMapping['datatype']);
      }
      if (!isset($fieldRDFMapping['datatype']) && ($reltype != 'property')) {
        $termDefinition = array('@type' => '@id');
      }

      // This should respect user provided mapping and fill rest with defaults.
      $termDefinition = $termDefinition + $default_field_term_mapping;

      // Now iterate over all properties for this field
      // trying to parse them as compact IRI.
      foreach ($fieldRDFMapping['properties'] as $property) {
        $compactedDefinition = $this->parseCompactedIri($property);
        if ($compactedDefinition['prefix'] != NULL) {
          // Check if the namespace prefix exists.
          if (array_key_exists($compactedDefinition['prefix'], $allRdfNameSpaces)) {
            // Just overwrite as many times as needed,
            // still faster than checking if
            // it's there in the first place.
            $fieldContextFragment[$compactedDefinition['prefix']] = $allRdfNameSpaces[$compactedDefinition['prefix']];
            $fieldContextFragment[$property] = $termDefinition;
          }
        }
      }
    }

    return $fieldContextFragment;
  }

  /**
   * Writes JSON-LD @context cache per Entity_type bundle combo.
   *
   * @param RdfMappingInterface $rdfMapping
   *    Rdf mapping object.
   * @param string $cid
   *   Name of the cache bin to use.
   *
   * @return string
   *   A json encoded string for the processed JSON-LD @context
   */
  protected function writeCache(RdfMappingInterface $rdfMapping, $cid) {

    // This is how an empty json encoded @context looks like.
    $data = json_encode(array('@context' => ''), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    try {
      $data = $this->generateContext($rdfMapping);
      $this->cache->set($cid, $data, Cache::PERMANENT, $rdfMapping->getCacheTagsToInvalidate());
    }
    catch (\Exception $e) {
      $this->logger->warning("@msg",
        array(
          '@msg' => $e->getMessage(),
        ));
    }

    return $data;
  }

  /**
   * Absurdly simple exploder for a joint entityType and Bundle ids string.
   *
   * @param string $ids
   *    A string with containing entity id and bundle joined by a dot.
   *
   * @return array
   *    And array with the entity type and the bundle id
   */
  protected function entityBundleIdsSplitter($ids) {
    list($entity_type_id, $bundle_id) = explode(".", $ids, 2);
    return array('entityTypeId' => $entity_type_id, 'bundleId' => $bundle_id);
  }

  /**
   * Parses and IRI, checks if it is complaint with compacted IRI definition.
   *
   * Assumes this notion of compact IRI/similar to CURIE
   * http://json-ld.org/spec/ED/json-ld-syntax/20120522/#dfn-prefix.
   *
   * @param string $iri
   *    IRIs are strings.
   *
   * @return array
   *    If $iri is a compacted iri, prefix and term as separate
   *    array members, if not, unmodified $iri in term position
   *    and null prefix.
   */
  protected function parseCompactedIri($iri) {
    // As naive as it gets.
    list($prefix, $rest) = array_pad(explode(":", $iri, 2), 2, '');
    if ((substr($rest, 0, 2) == "//") || ($prefix == $iri)) {
      // Means this was never a compacted IRI.
      return array('prefix' => NULL, 'term' => $iri);
    }
    return array('prefix' => $prefix, 'term' => $rest);
  }

  /**
   * Naive approach on Drupal field to JSON-LD type mapping.
   *
   * TODO: Would be fine to have this definitions in an
   * configEntity way in the future.
   *
   * @param string $field_type
   *    As provided by \Drupal\Core\Field\FieldDefinitionInterface::getType().
   *
   * @return array
   *    A json-ld term definition if there is a match
   *    or array("@type" => "xsd:string") in case of no match.
   */
  protected function getTermContextFromField($field_type) {
    // Be aware that drupal field definitions can be complex.
    // e.g text_with_summary has a text, a summary, a number of lines, etc
    // we are only dealing with the resulting ->value() of all this separate
    // pieces and mapping only that as a whole.
    // Default mapping to return in case no $field_type matches
    // field_mappings array keys.
    $default_mapping = array(
      "@type" => "xsd:string",
    );

    $field_mappings = array(
      "comment" => array(
        "@type" => "xsd:string",
      ),
      "datetime" => array(
        "@type" => "xsd:dateTime",
      ),
      "file" => array(
        "@type" => "@id",
      ),
      "image" => array(
        "@type" => "@id",
      ),
      "link" => array(
        "@type" => "xsd:anyURI",
      ),
      "list_float" => array(
        "@type" => "xsd:float",
        "@container" => "@list",
      ),
      "list_integer" => array(
        "@type" => "xsd:int",
        "@container" => "@list",
      ),
      "list_string" => array(
        "@type" => "xsd:string",
        "@container" => "@list",
      ),
      "path" => array(
        "@type" => "xsd:anyURI",
      ),
      "text" => array(
        "@type" => "xsd:string",
      ),
      "text_with_summary" => array(
        "@type" => "xsd:string",
      ),
      "text_long" => array(
        "@type" => "xsd:string",
      ),
      "uuid" => array(
        "@type" => "xsd:string",
      ),
      "uri" => array(
        "@type" => "xsd:anyURI",
      ),
      "language" => array(
        "@type" => "xsd:language",
      ),
      "string_long" => array(
        "@type" => "xsd:string",
      ),
      "changed" => array(
        "@type" => "xsd:dateTime",
      ),
      "map" => "xsd:",
      "boolean" => array(
        "@type" => "xsd:boolean",
      ),
      "email" => array(
        "@type" => "xsd:string",
      ),
      "integer" => array(
        "@type" => "xsd:int",
      ),
      "decimal" => array(
        "@type" => "xsd:decimal",
      ),
      "created" => array(
        "@type" => "xsd:dateTime",
      ),
      "float" => array(
        "@type" => "xsd:float",
      ),
      "entity_reference" => array(
        "@type" => "@id",
      ),
      "timestamp" => array(
        "@type" => "xsd:dateTime",
      ),
      "string" => array(
        "@type" => "xsd:string",
      ),
      "password" => array(
        "@type" => "xsd:string",
      ),
    );

    return array_key_exists($field_type, $field_mappings) ? $field_mappings[$field_type] : $default_mapping;

  }

}
