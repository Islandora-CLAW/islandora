<?php
namespace Drupal\islandora\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * Tests creating node views with the wizard.
 *
 * Mostly copied from Nodes wizard, look there for more options.
 *
 * @ViewsWizard(
 *   id = "fedora_resource",
 *   base_table = "fedora_resource_field_data",
 *   title = @Translation("Fedora Resource")
 * )
 */
class FedoraResource extends WizardPluginBase {

  /**
   * Set the created column.
   */
  protected $createdColumn = 'fedora_resource_field_data-created';

  /**
   * Overrides Drupal\views\Plugin\views\wizard\WizardPluginBase::getAvailableSorts().
   *
   * @return array
   *   An array whose keys are the available sort options and whose
   *   corresponding values are human readable labels.
   */
  public function getAvailableSorts() {
    // You can't execute functions in properties, so override the method
    return array(
      'fedora_resource_field_data-name:ASC' => $this->t('Name')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultDisplayOptions() {
    $display_options = parent::defaultDisplayOptions();

    // Add permission-based access control.
    $display_options['access']['type'] = 'perm';
    $display_options['access']['options']['perm'] = 'access content';

    // Remove the default fields, since we are customizing them here.
    unset($display_options['fields']);

    // Add the title field, so that the display has content if the user switches
    // to a row style that uses fields.
    /* Field: Content: Title */
    $display_options['fields']['name']['id'] = 'name';
    $display_options['fields']['name']['table'] = 'fedora_resource_field_data';
    $display_options['fields']['name']['field'] = 'name';
    $display_options['fields']['name']['entity_type'] = 'fedora_resource';
    $display_options['fields']['name']['entity_field'] = 'name';
    $display_options['fields']['name']['label'] = '';
    $display_options['fields']['name']['alter']['alter_text'] = 0;
    $display_options['fields']['name']['alter']['make_link'] = 0;
    $display_options['fields']['name']['alter']['absolute'] = 0;
    $display_options['fields']['name']['alter']['trim'] = 0;
    $display_options['fields']['name']['alter']['word_boundary'] = 0;
    $display_options['fields']['name']['alter']['ellipsis'] = 0;
    $display_options['fields']['name']['alter']['strip_tags'] = 0;
    $display_options['fields']['name']['alter']['html'] = 0;
    $display_options['fields']['name']['hide_empty'] = 0;
    $display_options['fields']['name']['empty_zero'] = 0;
    $display_options['fields']['name']['settings']['link_to_entity'] = 1;
    $display_options['fields']['name']['plugin_id'] = 'field';

    return $display_options;
  }

  /**
   * {@inheritdoc}
   */
  protected function pageDisplayOptions(array $form, FormStateInterface $form_state) {
    $display_options = parent::pageDisplayOptions($form, $form_state);
    $row_plugin = $form_state->getValue(array('page', 'style', 'row_plugin'));
    $row_options = $form_state->getValue(array('page', 'style', 'row_options'), array());
    $this->display_options_row($display_options, $row_plugin, $row_options);
    return $display_options;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockDisplayOptions(array $form, FormStateInterface $form_state) {
    $display_options = parent::blockDisplayOptions($form, $form_state);
    $row_plugin = $form_state->getValue(array('block', 'style', 'row_plugin'));
    $row_options = $form_state->getValue(array('block', 'style', 'row_options'), array());
    $this->display_options_row($display_options, $row_plugin, $row_options);
    return $display_options;
  }

  /**
   * Set the row style and row style plugins to the display_options.
   */
  protected  function display_options_row(&$display_options, $row_plugin, $row_options) {
    switch ($row_plugin) {
      case 'full_posts':
        $display_options['row']['type'] = 'entity:fedora_resource';
        $display_options['row']['options']['view_mode'] = 'full';
        break;
      case 'teasers':
        $display_options['row']['type'] = 'entity:fedora_resource';
        $display_options['row']['options']['view_mode'] = 'teaser';
        break;
      case 'titles_linked':
      case 'titles':
        $display_options['row']['type'] = 'fields';
        $display_options['fields']['title']['id'] = 'title';
        $display_options['fields']['title']['table'] = 'fedora_resource_field_data';
        $display_options['fields']['title']['field'] = 'title';
        $display_options['fields']['title']['settings']['link_to_entity'] = $row_plugin === 'titles_linked';
        $display_options['fields']['title']['plugin_id'] = 'field';
        break;
    }
  }
}