<?php

namespace Drupal\Tests\islandora\Functional;

/**
 * Tests the DeleteMedia and DeleteMediaAndFile actions.
 *
 * @group islandora
 */
class DeleteMediaTest extends IslandoraFunctionalTestBase {

  /**
   * Media.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $media;

  /**
   * File to belong to the media.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $file;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a test user.
    $account = $this->createUser(['create media']);

    list($this->file, $this->media) = $this->makeMediaAndFile($account);
  }

  /**
   * Tests the delete_media_and_file action.
   *
   * @covers \Drupal\islandora\Plugin\Action\DeleteMediaAndFile::execute
   */
  public function testDeleteMediaAndFile() {
    $action = $this->container->get('entity_type.manager')->getStorage('action')->load('delete_media_and_file');

    $mid = $this->media->id();
    $fid = $this->file->id();

    $action->execute([$this->media]);

    // Attempt to reload the entities.
    // Both media and file should be gone.
    $this->assertTrue(
      !$this->container->get('entity_type.manager')->getStorage('media')->load($mid),
      "Media must be deleted after running action"
    );
    $this->assertTrue(
      !$this->container->get('entity_type.manager')->getStorage('file')->load($fid),
      "File must be deleted after running action"
    );
  }

}
