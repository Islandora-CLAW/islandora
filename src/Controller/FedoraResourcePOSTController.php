<?php

namespace Drupal\islandora\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\islandora\RdfBundleSolver\JsonldContextGeneratorInterface;
use Drupal\rdf\Entity\RdfMapping;
use ML\JsonLD\JsonLD;

/**
 * Class FedoraResourcePOSTController.
 *
 * @package Drupal\islandora\Controller
 */
class FedoraResourcePOSTController extends ControllerBase {

  const HEADER_CONTENT_TYPE = 'Content-Type';
  const HEADER_BUNDLE = 'X-Islandora-Bundle';

  protected $jsonldGenerator;
  protected $entityManager;
  protected $entityFieldManager;

  /**
   * FedoraResourcePOSTController constructor.
   *
   * @param \Drupal\islandora\RdfBundleSolver\JsonldContextGeneratorInterface $generator
   *   Needed to get bundle's rdf context.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   Needed to do error checking.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Needed to get the fields info.
   */
  public function __construct(JsonldContextGeneratorInterface $generator, EntityManagerInterface $entityManager, EntityFieldManagerInterface $entityFieldManager) {
    $this->jsonldGenerator = $generator;
    $this->entityManager = $entityManager;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * Create required static object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Have to review this method!.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('islandora.jsonldcontextgenerator'),
      $container->get('entity.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Main method to handle the post request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   HTTP Rquest.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   HTTP Response.
   */
  public function processPost(Request $request) {

    // Get Headers.
    $contentType = $request->headers->get(self::HEADER_CONTENT_TYPE);
    $bundle = $request->headers->get(self::HEADER_BUNDLE);
    $entity_type = 'fedora_resource';

    // If X-Islandora-Bundle is not set, return bad request.
    if (!isset($bundle) || $bundle == '') {
      $response['data'] = "X-Islandora-Bundle header not defined";
      return new JsonResponse($response, 400);
    }

    // If X-Islandora-Bundle is not a valid one, return bad request.
    $bundles = $this->entityManager->getBundleInfo($entity_type);
    if (!array_key_exists($bundle, $bundles)) {
      $response['data'] = "Bundle not found.";
      return new JsonResponse($response, 400);
    }

    $newResourceID = "";
    try {
      // Request Body.
      $content = json_decode($request->getContent(), TRUE);
      $newResourceID = $this->createEntity($entity_type, $bundle, $content);

      if ($newResourceID != '') {
        $responseData = 'Created entity with url ' . $newResourceID;
        $responseStatus = 201;
      }
      else {
        $responseData = 'RDF Mapping not set.';
        $responseStatus = 500;
      }

    }
    catch (Exception $e) {
      \Drupal::logger('islandora')->error(
        'Failed to create entity: @msg', ['@msg' => $e->getMessage()]
      );
      $responseData = "Failed to create entity.";
      $responseStatus = 500;
    }

    $responseInfo['data'] = $responseData;
    $response = new JsonResponse($responseInfo, $responseStatus);
    $response->headers->set('Content-Type', 'application/json');
    $response->headers->set('location', $newResourceID);

    return $response;
  }

  /**
   * Business Logic to create an entity from POST request.
   *
   * @param string $entity_type
   *   Fedora_resource.
   * @param string $bundle
   *   Type of bundle to be created, i.e image, collection.
   * @param object $content
   *   The request body.
   *
   * @return string
   *   Id of the entity that was created.
   */
  private function createEntity($entity_type, $bundle, $content) {
    // Field -> RDFMapping - [name] => Array([0] => dc11:title [1] => rdf:label.
    $arrFieldsWithRDFMapping = $this->getFieldsWithRdfMapping($entity_type, $bundle);

    if (count($arrFieldsWithRDFMapping) == 0) {
      return '';
    }

    // Sort the fields by rdf mapping and get fieldNames for comparison.
    asort($arrFieldsWithRDFMapping);
    $fieldNames = array_keys($arrFieldsWithRDFMapping);

    // Get the expanded JsonLD of the Entity.
    $arrEntityExpandedJsonLD = $this->getEntityExpandedJsonLd($entity_type, $bundle, $arrFieldsWithRDFMapping);
    $arrEntityExpandedJsonLD = json_decode(JsonLD::toString($arrEntityExpandedJsonLD[0], TRUE), TRUE);

    // Create Entity.
    $entity = entity_create($entity_type, ['type' => $bundle]);

    // Loop through all properties.
    foreach ($content as $property => $fieldValue) {
      // This gets auto created!
      if ($property === "@type") {
        continue;
      }

      // Match the request RDF property with the Entity mapped property.
      $fieldKey = array_search($property, array_keys($arrEntityExpandedJsonLD));
      $fieldName = $fieldNames[$fieldKey];
      if ($fieldName) {
        $value = $fieldValue[0]["@value"];
        $entity->$fieldName = $value;
      }
    }

    $entity->save();
    $url = $entity->toUrl()->setAbsolute()->toString();

    return $url;
  }

  /**
   * Get Expanded JsonLD of the Entity.
   *
   * @param string $entity_type
   *   Entity type's name.
   * @param string $bundle
   *   Bundle's name.
   * @param array $arrFieldsWithRDFMapping
   *   Field name and rdf mapping array.
   *
   * @return array
   *   Expanded JsonLD of the Entity.
   */
  private function getEntityExpandedJsonLd($entity_type, $bundle, array $arrFieldsWithRDFMapping) {
    // Get Context.
    $bundleContext = $this->jsonldGenerator->getContext($entity_type . "." . $bundle);
    $contextInfo = json_decode($bundleContext);

    // Put fields into a document.
    $arrEntityDocument = [];
    foreach ($arrFieldsWithRDFMapping as $k => $v) {
      $arrEntityDocument[$v] = '';
    }

    $compacted = JsonLD::compact((object) $arrEntityDocument, (object) $contextInfo);
    $entityExpandedJsonLD = JsonLD::expand($compacted);

    return $entityExpandedJsonLD;
  }

  /**
   * Returns the RDF Mapping of the fields, if RDF Mapping is available.
   *
   * @param string $entity_type
   *   Entity Type's name.
   * @param string $bundle
   *   Bundle's name.
   *
   * @return array
   *   Field to RDF Mapping.
   */
  private function getFieldsWithRdfMapping($entity_type, $bundle) {
    $arrFieldsWithRDFMapping = [];

    // Get Fields.
    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);

    // Get RDF Mapping.
    $rdfMapping = RdfMapping::load($entity_type . "." . $bundle);
    if (!$rdfMapping) {
      return $arrFieldsWithRDFMapping;
    }

    foreach ($fields as $field_name => $field_definition) {
      $arrFieldMapping = $rdfMapping->getFieldMapping($field_name);
      if (isset($arrFieldMapping['properties']) && count($arrFieldMapping["properties"]) > 0) {
        $arrFieldsWithRDFMapping[$field_name] = $arrFieldMapping["properties"][0];
      }
    }

    return $arrFieldsWithRDFMapping;
  }

}
