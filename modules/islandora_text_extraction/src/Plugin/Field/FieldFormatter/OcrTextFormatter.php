<?php

namespace Drupal\islandora_text_extraction\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Plugin implementation of the 'ocr_txt_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "ocr_formatter",
 *   label = @Translation("OCRed text formatter"),
 *   field_types = {"file"}
 * )
 */
class OcrTextFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
              // Implement default settings.
          ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
              // Implement settings form.
          ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $this->viewValue($item)];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    $fileItem = $item->getValue();
    $file = File::load($fileItem['target_id']);
    $contents = file_get_contents($file->getFileUri());
    if (mb_detect_encoding($contents) != 'UTF-8') {
      $contents = utf8_encode($contents);
    }
    $contents = nl2br($contents);
    return $contents;
  }

}
