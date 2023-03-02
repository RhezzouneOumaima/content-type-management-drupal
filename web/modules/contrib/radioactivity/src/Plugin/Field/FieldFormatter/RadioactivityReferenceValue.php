<?php

namespace Drupal\radioactivity\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'radioactivity_reference_value' formatter.
 *
 * @FieldFormatter(
 *   id = "radioactivity_reference_value",
 *   label = @Translation("Value"),
 *   field_types = {
 *     "radioactivity_reference"
 *   }
 * )
 */
class RadioactivityReferenceValue extends RadioactivityReferenceFormatterBase {

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

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      if (empty($entity) || !$entity->id()) {
        continue;
      }

      $elements[$delta]['value'] = [
        '#lazy_builder' => [
          'radioactivity.lazy_builder:buildReferencedValue',
          [
            $entity->id(),
            $this->getSetting('decimals'),
          ],
        ],
        '#create_placeholder' => TRUE,
      ];
    }

    return $elements;
  }

}
