<?php

namespace Drupal\islandora\VersionCounter;

use Drupal\Core\Database\Connection;

/**
 * Default VersionCounterInterface implemenation.
 *
 * Uses the drupal database.
 */
class VersionCounter implements VersionCounterInterface {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * VersionCounter constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function create($uuid) {
    $this->database->insert('islandora_version_count')
      ->fields([
        'uuid' => $uuid,
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function get($uuid) {
    $query = $this->database->select('islandora_version_count', 'ivc')
      ->condition('ivc.uuid', $uuid)
      ->fields('ivc', ['count']);

    $results = $query->execute();

    foreach ($results as $result) {
      return $result->count;
    }

    return -1;
  }

  /**
   * {@inheritdoc}
   */
  public function increment($uuid) {
    return $this->database->update('islandora_version_count')
      ->expression('count', 'count + 1')
      ->condition('uuid', $uuid)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function delete($uuid) {
    return $this->database->delete('islandora_version_count')
      ->condition('uuid', $uuid)
      ->execute();
  }

}
