<?php

namespace Drupal\islandora\MediaSource;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\Utility\Token;
use Drupal\file\FileInterface;
use Drupal\media_entity\MediaInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Utility functions for working with source files for Media.
 */
class MediaSourceService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManager
   */
  protected $streamWrapperManager;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManager $stream_wrapper_manager
   *   Stream wrapper manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager.
   * @param \Drupal\Core\Utility\Token $token
   *   Token service.
   */
  public function __construct(
    EntityTypeManager $entity_type_manager,
    AccountInterface $account,
    StreamWrapperManager $stream_wrapper_manager,
    LanguageManagerInterface $language_manager,
    Token $token
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->account = $account;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->languageManager = $language_manager;
    $this->token = $token;
  }

  /**
   * Gets the name of a source field for a Media.
   *
   * @param string $media_bundle
   *   Media bundle whose source field you are searching for.
   *
   * @return string|null
   *   Field name if it exists in configuration, else NULL.
   */
  public function getSourceFieldName($media_bundle) {
    $bundle = $this->entityTypeManager->getStorage('media_bundle')->load($media_bundle);
    if (!$bundle) {
      throw new NotFoundHttpException("Bundle $media_bundle does not exist");
    }

    $type_configuration = $bundle->getTypeConfiguration();
    if (!isset($type_configuration['source_field'])) {
      return NULL;
    }

    return $type_configuration['source_field'];
  }

  /**
   * Updates a media's source field with the supplied resource.
   *
   * @param \Drupal\media_entity\MediaInterface $media
   *   The media to update.
   * @param resource $resource
   *   New file contents as a resource.
   * @param string $mimetype
   *   New mimetype of contents.
   *
   * @throws HttpException
   */
  public function updateSourceField(
    MediaInterface $media,
    $resource,
    $mimetype
  ) {
    // Get the source field for the media type.
    $source_field = $this->getSourceFieldName($media->bundle());

    if (empty($source_field)) {
      throw new NotFoundHttpException("Source field not set for {$media->bundle()} media");
    }

    // Get the file from the media.
    $files = $media->get($source_field)->referencedEntities();
    $file = reset($files);

    // Update it.
    $this->updateFile($file, $resource, $mimetype);

    // Set fields provided by type plugin and mapped in bundle configuration
    // for the media.
    foreach ($media->bundle->entity->field_map as $source => $destination) {
      if ($media->hasField($destination) && $value = $media->getType()->getField($media, $source)) {
        $media->set($destination, $value);
        // Ensure width and height are updated on File reference when it's an
        // image. Otherwise you run into scaling problems when updating images
        // with different sizes.
        if ($source == 'width' || $source == 'height') {
          $media->get($source_field)->first()->set($source, $value);
        }
      }
    }

    $media->save();
  }

  /**
   * Updates a File's binary contents on disk.
   *
   * @param \Drupal\file\FileInterface $file
   *   File to update.
   * @param resource $resource
   *   Stream holding the new contents.
   * @param string $mimetype
   *   Mimetype of new contents.
   */
  protected function updateFile(FileInterface $file, $resource, $mimetype = NULL) {
    $uri = $file->getFileUri();
    $file_stream_wrapper = $this->streamWrapperManager->getViaUri($uri);
    $path = "";
    $file_stream_wrapper->stream_open($uri, 'w', STREAM_REPORT_ERRORS, $path);
    $file_stream = $file_stream_wrapper->stream_cast(STREAM_CAST_AS_STREAM);
    $content_length = stream_copy_to_stream($resource, $file_stream);

    if ($content_length === FALSE) {
      throw new HttpException(500, "Request body could not be copied to $uri");
    }

    if ($content_length === 0) {
      // Clean up the newly created, empty file.
      $file_stream_wrapper->unlink($uri);
      throw new BadRequestHttpException("The request contents are empty.");
    }

    if (!empty($mimetype)) {
      $file->setMimeType($mimetype);
    }

    // Flush the image cache for the image so thumbnails get regenerated.
    image_path_flush($uri);
  }

  /**
   * Creates a new Media using the provided resource, adding it to a Node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to reference the newly created Media.
   * @param string $field
   *   Name of field on the Node to reference the Media.
   * @param string $bundle
   *   Bundle of Media to create.
   * @param resource $resource
   *   New file contents as a resource.
   * @param string $mimetype
   *   New mimetype of contents.
   * @param string $filename
   *   New filename for contents.
   *
   * @throws HttpException
   */
  public function addToNode(
    NodeInterface $node,
    $field,
    $bundle,
    $resource,
    $mimetype,
    $filename
  ) {
    if (!$node->hasField($field)) {
      throw new NotFoundHttpException();
    }

    // Filter out any bad references before confirming it is empty.
    $node->get($field)->filter(function ($elem) {
      $value = $elem->getValue();
      $mid = $value['target_id'];
      return $this->entityTypeManager->getStorage('media')->load($mid);
    });

    if ($node->get($field)->count()) {
      throw new ConflictHttpException();
    }

    // Get the source field for the media type.
    $source_field = $this->getSourceFieldName($bundle);
    if (empty($source_field)) {
      throw new NotFoundHttpException("Source field not set for {$media->bundle()} media");
    }

    // Load its config to get file extensions and upload path.
    $source_field_config = $this->entityTypeManager->getStorage('field_config')->load("media.$bundle.$source_field");

    // Construct the destination uri.
    $directory = $source_field_config->getSetting('file_directory');
    $directory = trim($directory, '/');
    $directory = PlainTextOutput::renderFromHtml($this->token->replace($directory, ['node' => $node]));
    $scheme = file_default_scheme();
    $destination_directory = "$scheme://$directory";
    $destination = "$destination_directory/$filename";

    // Construct the File.
    $file = $this->entityTypeManager->getStorage('file')->create([
      'uid' => $this->account->id(),
      'uri' => $destination,
      'filename' => $filename,
      'filemime' => $mimetype,
      'status' => FILE_STATUS_PERMANENT,
    ]);

    // Validate file extension.
    $source_field_config = $this->entityTypeManager->getStorage('field_config')->load("media.$bundle.$source_field");
    $valid_extensions = $source_field_config->getSetting('file_extensions');
    $errors = file_validate_extensions($file, $valid_extensions);

    if (!empty($errors)) {
      throw new BadRequestHttpException("Invalid file extension.  Valid types are $valid_extensions");
    }

    if (!file_prepare_directory($destination_directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      throw new HttpException(500, "The destination directory does not exist, could not be created, or is not writable");
    }

    // Copy over the file content.
    $this->updateFile($file, $resource, $mimetype);
    $file->save();

    // Construct the Media.
    $media_struct = [
      'bundle' => $bundle,
      'uid' => $this->account->id(),
      'name' => $filename,
      'langcode' => $this->languageManager->getDefaultLanguage()->getId(),
      "$source_field" => [
        'target_id' => $file->id(),
      ],
    ];
    if ($source_field_config->getSetting('alt_field') && $source_field_config->getSetting('alt_field_required')) {
      $media_struct[$source_field]['alt'] = $filename;
    }
    $media = $this->entityTypeManager->getStorage('media')->create($media_struct);
    $media->save();

    // Update the Node.
    $node->set($field, $media);
    $node->save();

    // Return the created media.
    return $media;
  }

}
