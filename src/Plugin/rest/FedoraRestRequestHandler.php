<?php

namespace Drupal\islandora\Plugin\rest;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\islandora\IslandoraConstants;
use Drupal\rest\RequestHandler;
use Drupal\rest\ResourceResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Defines custom rest plugin functions for FedoraResource entities.
 *
 * @package Drupal\islandora\Plugin\rest
 */
class FedoraRestRequestHandler extends RequestHandler {

  /**
   * Handles a web API request.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function handle(RouteMatchInterface $route_match, Request $request) {
    $method = strtolower($request->getMethod());

    // Symfony is built to transparently map HEAD requests to a GET request. In
    // the case of the REST module's RequestHandler though, we essentially have
    // our own light-weight routing system on top of the Drupal/symfony routing
    // system. So, we have to do the same as what the UrlMatcher does: map HEAD
    // requests to the logic for GET. This also guarantees response headers for
    // HEAD requests are identical to those for GET requests, because we just
    // return a GET response. Response::prepare() will transform it to a HEAD
    // response at the very last moment.
    // @see https://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
    // @see \Symfony\Component\Routing\Matcher\UrlMatcher::matchCollection()
    // @see \Symfony\Component\HttpFoundation\Response::prepare()
    if ($method === 'head') {
      $method = 'get';
    }

    $resource_config_id = $route_match->getRouteObject()->getDefault('_rest_resource_config');
    /** @var \Drupal\rest\RestResourceConfigInterface $resource_config */
    $resource_config = $this->resourceStorage->load($resource_config_id);
    $resource = $resource_config->getResourcePlugin();

    // Deserialize incoming data if available.
    /** @var \Symfony\Component\Serializer\SerializerInterface $serializer */
    $serializer = $this->container->get('serializer');
    $received = $request->getContent();
    $unserialized = NULL;
    if (!empty($received)) {
      $format = $request->getContentType();

      // Only allow serialization formats that are explicitly configured. If no
      // formats are configured allow all and hope that the serializer knows the
      // format. If the serializer cannot handle it an exception will be thrown
      // that bubbles up to the client.
      $request_method = $request->getMethod();

      $context = ['request_method' => $request_method, 'entity_type_id' => 'fedora_resource'];
      if ($request->headers->has(IslandoraConstants::ISLANDORA_BUNDLE_HEADER)) {
        $bundle_type_id = $request->headers->get(IslandoraConstants::ISLANDORA_BUNDLE_HEADER);
        if (is_array($bundle_type_id)) {
          $bundle_type_id = reset($bundle_type_id);
        }
        $context['bundle_type_id'] = $bundle_type_id;
      }
      if (in_array($format, $resource_config->getFormats($request_method))) {
        $definition = $resource->getPluginDefinition();
        try {
          if (!empty($definition['serialization_class'])) {
            $unserialized = $serializer->deserialize($received, $definition['serialization_class'], $format, $context);
          }
          // If the plugin does not specify a serialization class just decode
          // the received data.
          else {
            $unserialized = $serializer->decode($received, $format, $context);
          }
        }
        catch (UnexpectedValueException $e) {
          $error['error'] = $e->getMessage();
          $content = $serializer->serialize($error, $format);
          return new Response($content, 400, array('Content-Type' => $request->getMimeType($format)));
        }
      }
      else {
        throw new UnsupportedMediaTypeHttpException();
      }
    }

    // Determine the request parameters that should be passed to the resource
    // plugin.
    $route_parameters = $route_match->getParameters();
    $parameters = array();
    // Filter out all internal parameters starting with "_".
    foreach ($route_parameters as $key => $parameter) {
      if ($key{0} !== '_') {
        $parameters[] = $parameter;
      }
    }

    // Invoke the operation on the resource plugin.
    $format = $this->getResponseFormat($route_match, $request);
    $response = call_user_func_array(array($resource, $method), array_merge($parameters, array($unserialized, $request)));

    return $response instanceof ResourceResponseInterface ?
      $this->renderResponse($request, $response, $serializer, $format, $resource_config) :
      $response;
  }

}
