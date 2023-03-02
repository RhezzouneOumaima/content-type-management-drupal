<?php

namespace Drupal\radioactivity\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'radioactivity_value' formatter.
 *
 * @FieldFormatter(
 *   id = "radioactivity_value",
 *   label = @Translation("Value"),
 *   field_types = {
 *     "radioactivity"
 *   }
 * )
 */
class RadioactivityValue extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'decimals' => 0,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      'decimals' => [
        '#title' => $this->t('Decimals'),
        '#type' => 'number',
        '#min' => 0,
        '#required' => TRUE,
        '#description' => $this->t('The number of decimals to show.'),
        '#default_value' => $this->getSetting('decimals'),
      ],
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Decimals: @number', ['@number' => $this->getSetting('decimals')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if (!$item->isEmpty()) {
        $elements[$delta] = [
          '#markup' => $this->viewValue($item),
        ];
      }
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
   *   The output generated.
   */
  protected function viewValue(FieldItemInterface $item): string {
    return number_format($item->energy, $this->getSetting('decimals'));
  }

}
