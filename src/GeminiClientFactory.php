<?php

namespace Drupal\islandora;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\islandora\Form\IslandoraSettingsForm;
use Islandora\Crayfish\Commons\Client\GeminiClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

/**
 * Creates a GeminiClient as a Drupal service.
 *
 * @package Drupal\islandora
 */
class GeminiClientFactory {

  /**
   * Factory function.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Config.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   *
   * @return \Islandora\Crayfish\Commons\Client\GeminiClient
   *   Return GeminiClient
   *
   * @throws \Exception
   *   If there is no URL to connect to.
   */
  public static function create(ConfigFactoryInterface $config, LoggerInterface $logger) {
    // Get broker url from config.
    $settings = $config->get(IslandoraSettingsForm::CONFIG_NAME);
    $geminiUrl = $settings->get(IslandoraSettingsForm::GEMINI_URL);

    // Only attempt if there is one.
    if (!empty($geminiUrl)) {
      return GeminiClient::create($geminiUrl, $logger);
    }
    else {
      $logger->notice("Attempted to create Gemini client without a Gemini URL defined.");
      throw new PreconditionFailedHttpException("Unable to instantiate GeminiClient, missing Gemini URI in Islandora setting.");
    }
  }

}
