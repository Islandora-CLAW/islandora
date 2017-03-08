<?php

namespace Drupal\islandora;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\islandora\Form\IslandoraSettingsForm;
use Stomp\Client;
use Stomp\StatefulStomp;

/**
 * StatefulStomp static factory.
 */
class StompFactory {

  /**
   * Factory function.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Config.
   *
   * @return \Stomp\StatefulStomp
   *   Stomp client.
   */
  public static function create(ConfigFactoryInterface $config) {
    // Get broker url from config.
    $settings = $config->get(IslandoraSettingsForm::CONFIG_NAME);
    $brokerUrl = $settings->get(IslandoraSettingsForm::BROKER_URL);

    $client = new Client($brokerUrl);
    return new StatefulStomp($client);
  }

}
