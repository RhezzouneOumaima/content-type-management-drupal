<?php

namespace Drupal\radioactivity\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Element;
use Drupal\radioactivity\Incident;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'radioactivity_reference_emitter' formatter.
 *
 * @FieldFormatter(
 *   id = "radioactivity_reference_emitter",
 *   label = @Translation("Emitter"),
 *   field_types = {
 *     "radioactivity_reference"
 *   },
 * )
 */
class RadioactivityReferenceEmitter extends RadioactivityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'energy' => 10,
      'display' => FALSE,
      'decimals' => 0,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    return [
      'energy' => [
        '#title' => $this->t('Energy to emit'),
        '#type' => 'textfield',
        '#required' => TRUE,
        '#description' => $this->t('The amount of energy to emit when this field is displayed. Examples: 0.5, 10.'),
        '#pattern' => '[0-9]+(\.[0-9]+)?',
        '#default_value' => $this->getSetting('energy'),
      ],
      'display' => [
        '#title' => $this->t('Display current energy value'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSetting('display'),
      ],
      'decimals' => [
        '#title' => $this->t('Decimals'),
        '#type' => 'number',
        '#min' => 0,
        '#required' => TRUE,
        '#description' => $this->t('The number of decimals to show.'),
        '#default_value' => $this->getSetting('decimals'),
        '#states' => [
          'visible' => [
            'input[name="fields[' . $field_name . '][settings_edit_form][settings][display]"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Emit: @energy', ['@energy' => $this->getSetting('energy')]);
    if ($this->getSetting('display')) {
      $summary[] = $this->t('Display energy value');
      $summary[] = $this->t('Decimals: @number', ['@number' => $this->getSetting('decimals')]);
    }
    else {
      $summary[] = $this->t('Only emit');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $shouldEmit = $this->shouldEmit($items);

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      if (empty($entity) || !$entity->id()) {
        continue;
      }

      if ($shouldEmit) {
        $incident = Incident::createFromFieldItemsAndFormatter($items, $entity->_referringItem, $this);
        $key = 'ra_emit_' . radioactivity_unique_emit_id();

        $elements[$delta]['trigger'] = [
          '#attached' => [
            'library' => ['radioactivity/triggers'],
            'drupalSettings' => [
              $key => $incident->toJson(),
            ],
          ],
        ];
      }

      if ($this->getSetting('display')) {
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
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {

    $build = parent::view($items, $langcode);
    // If the energy value is not displayed, we do not want this formatter to be
    // rendered as field (it would be rendered in an empty wrapper div). We only
    // use the children which contain the energy emitter.
    if (!$this->getSetting('display')) {
      $children = Element::children($build);
      $build = array_intersect_key($build, $children);
    }

    return $build;
  }

  /**
   * Determine if the field should emit energy.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field item list of the field.
   *
   * @return bool
   *   True if so.
   */
  protected function shouldEmit(FieldItemListInterface $items): bool {
    $entity = $items->getEntity();
    if (!$entity instanceof EntityPublishedInterface) {
      return TRUE;
    }

    return $entity->isPublished();
  }

}
