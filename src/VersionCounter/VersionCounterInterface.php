<?php

namespace Drupal\islandora\VersionCounter;

/**
 * Service for tracking the number of times an entity has been updated.
 */
interface VersionCounterInterface {

  /**
   * Creates a version count record in the db for an entity.
   *
   * @param string $uuid
   *   Entity UUID.
   *
   * @throws Drupal\Core\Database\IntegrityConstraintViolationException
   *
   * @return int
   *   The id of the newly created db record.
   */
  public function create($uuid);

  /**
   * Returns the version count for an entity.
   *
   * @param string $uuid
   *   Entity UUID.
   *
   * @return int
   *   The version count of the entity. Returns -1 if there is no record for the
   *   uuid in the database.
   */
  public function get($uuid);

  /**
   * Increments a version count for an entity.
   *
   * @param string $uuid
   *   Entity UUID.
   *
   * @return int
   *   Returns 1 on success.  Returns 0 if no record exists for the uuid in the
   *   database.
   */
  public function increment($uuid);

  /**
   * Deletes a version count record in the db for an entity.
   *
   * @param string $uuid
   *   Entity UUID.
   *
   * @return int
   *   Returns 1 on success.  Returns 0 if no record exists for the uuid in the
   *   database.
   */
  public function delete($uuid);

  /**
   * Test the current version count against an external value.
   *
   * @param string $uuid
   *   Entity UUID.
   * @param int $test_value
   *   Test version count value.
   *
   * @return int
   *   Returns 1 on match. Otherwise returns 0.
   */
  public function isValid($uuid, $test_value);

}
