<?php

namespace Drupal\islandora_iiif\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Symfony\Component\Serializer\SerializerInterface;
use Drupal\openseadragon\File\FileInformationInterface;
use Drupal\openseadragon\ConfigInterface;

/**
 * Provide serializer format for IIIF Manifest.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "iiif_manifest",
 *   title = @Translation("IIIF Manifest"),
 *   help = @Translation("Display images as an IIIF Manifest."),
 *   display_types = {"data"}
 * )
 */
class IIIFManifest extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = FALSE;

  /**
   * The allowed formats for this serializer. Default to only JSON.
   *
   * @var array
   */
  protected $formats = ['json'];

  /**
   * The serializer which serializes the views result.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * Openseadragon config.
   *
   * @var \Drupal\openseadragon\ConfigInterface
   */
  protected $openseadragonConfig = NULL;

  /**
   * Openseadragon File Info service.
   *
   * @var \Drupal\openseadragon\File\FileInformationInterface
   */
  protected $fileinfoService = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SerializerInterface $serializer, ConfigInterface $openseadragon_config, FileInformationInterface $fileinfo_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->serializer = $serializer;
    $this->openseadragonConfig = $openseadragon_config;
    $this->fileinfoService = $fileinfo_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('serializer'),
      $container->get('openseadragon.config'),
      $container->get('openseadragon.fileinfo')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $json = [];
    $viewer_settings = $this->openseadragonConfig->getSettings(TRUE);
    $iiif_address = $this->openseadragonConfig->getIiifAddress();
    if (!is_null($iiif_address) && !empty($iiif_address)) {
      // Get Drupal's base URL to remove from IIIF image URL.
      $base_url = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
      // For each row in the View result.
      foreach ($this->view->result as $row) {
        // Add the IIIF URL to the image to print out as JSON.
        foreach ($this->getTileSourceFromRow($row, $base_url, $iiif_address) as $tile_source) {
          $json[] = $tile_source;
        }
      }
    }
    unset($this->view->row_index);

    $content_type = 'json';

    return $this->serializer->serialize($json, $content_type, ['views_style_plugin' => $this]);
  }

  /**
   * Render array from views result row.
   *
   * @param \Drupal\views\ResultRow $row
   *   Result row.
   * @param string $base_url
   *   The URL to the frontpage of the Drupal site.
   * @param string $iiif_address
   *   The URL to the IIIF server endpoint.
   *
   * @return array
   *   List of IIIF URLs to display in the Openseadragon viewer.
   */
  protected function getTileSourceFromRow(ResultRow $row, $base_url, $iiif_address) {
    $tile_sources = [];
    $viewsField = $this->view->field[$this->options['iiif_tile_field']];
    $entity = $viewsField->getEntity($row);

    if (isset($entity->{$viewsField->definition['field_name']})) {

      /** @var \Drupal\Core\Field\FieldItemListInterface $images */
      $images = $entity->{$viewsField->definition['field_name']};
      foreach ($images as $image) {
        $file = $image->entity;
        $resource = $this->fileinfoService->getFileData($file);

        // Remove $base_url from full_path.
        $path = $resource['full_path'];
        $path = str_replace($base_url, '', $path);

        // Create the IIIF URL.
        $tile_sources[] = rtrim($iiif_address, '/') . '/' . urlencode($path);
      }
    }

    return $tile_sources;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['iiif_tile_field'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $field_options = [];

    $fields = $this->displayHandler->getHandlers('field');
    /** @var \Drupal\views\Plugin\views\field\FieldPluginBase[] $fields */
    foreach ($fields as $field_name => $field) {
      if (!empty($field->options['type']) && in_array($field->options['type'], ['image', 'file'])) {
        $field_options[$field_name] = $field->adminLabel();
      }
    }

    // If no fields to choose from, add an error message indicating such.
    if (count($field_options) == 0) {
      drupal_set_message($this->t('No image or file fields were found in the View.
        You will need to add a field to this View'), 'error');
    }

    $form['iiif_tile_field'] = [
      '#title' => $this->t('Tile source field'),
      '#type' => 'select',
      '#default_value' => $this->options['iiif_tile_field'],
      '#description' => $this->t("The source of image for each entity."),
      '#options' => $field_options,
      // Only make the form element required if
      // we have more than one option to choose from
      // otherwise could lock up the form when setting up a View.
      '#required' => count($field_options) > 0,
    ];
  }

  /**
   * Returns an array of format options.
   *
   * @return string[]
   *   An array of the allowed serializer formats. In this case just JSON.
   */
  public function getFormats() {
    return ['json' => 'json'];
  }

}
