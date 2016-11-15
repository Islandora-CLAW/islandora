<?php

namespace Drupal\islandora\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Fedora resource entities.
 */
class FedoraResourceViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['fedora_resource']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Fedora resource'),
      'help' => $this->t('The Fedora resource ID.'),
    );

    $data['fedora_resource_field_data']['table']['wizard_id'] = 'fedora_resource';
    $data['fedora_resource_field_revision']['table']['wizard_id'] = 'fedora_resource_revision';

    return $data;
  }

}
