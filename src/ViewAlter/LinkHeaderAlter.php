<?php

namespace Drupal\islandora\ViewAlter;

/**
 * Base class for ViewAlters that need to add link headers to responses.
 */
abstract class LinkHeaderAlter {

  /**
   * Utility function to add link headers to the build array.
   *
   * @param array $build
   *   The build array.
   * @param string $header_str
   *   Link headers as string.
   */
  protected function addLinkHeaders(array &$build, $header_str) {
    // Create the entry in the build array if no headers have been added yet.
    if (!isset($build['#attached']['http_header'])) {
      $build['#attached']['http_header'] = [];
    }

    // Append to Link header string if it already exists.
    for ($i = 0; $i < count($build['#attached']['http_header']); ++$i) {
      if ($build['#attached']['http_header'][$i][0] == 'Link') {
        $build['#attached']['http_header'][$i][1] .= ", $header_str";
        return;
      }
    }

    // Otherwise make a new one.
    $build['#attached']['http_header'][] = ['Link', $header_str];
  }

}
