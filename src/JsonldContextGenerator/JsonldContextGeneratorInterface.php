<?php

namespace Drupal\islandora\JsonldContextGenerator;

use Drupal\rdf\RdfMappingInterface;

/**
 * Interface for a service that provides per Bundle JSON-LD Context generation.
 *
 * @ingroup: islandora
 */
interface JsonldContextGeneratorInterface {

  /**
   * Generates an JSON-LD Context string based on an RdfMapping object.
   *
   * @param \Drupal\rdf\Entity\RdfMapping|RdfMappingInterface $mapping
   *   An RDF Mapping Object.
   *
   * @return string
   *   A JSON-LD @context as string.
   *
   * @throws \Exception
   *    If no RDF mapping has no rdf:type assigned.
   */
  public function generateContext(RdfMappingInterface $mapping);

  /**
   * Returns an JSON-LD Context string.
   *
   * This method should be invoked if caching and speed is required.
   *
   * @param string $ids
   *   In the form of "entity_type.bundle_name".
   *
   * @return string
   *   A JSON-LD @context as string.
   *
   * @throws \Exception
   *    If no RDF mapping exists.
   */
  public function getContext($ids);

}
