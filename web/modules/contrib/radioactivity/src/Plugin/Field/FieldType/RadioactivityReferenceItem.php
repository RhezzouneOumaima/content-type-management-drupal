<?php

namespace Drupal\radioactivity\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the Radioactivity Reference field type.
 *
 * @FieldType(
 *   id = "radioactivity_reference",
 *   label = @Translation("Radioactivity Reference"),
 *   description = @Translation("This field stores the ID of an radioactivity energy entity."),
 *   category = @Translation("Reference"),
 *   list_class = "\Drupal\radioactivity\RadioactivityReferenceFieldItemList",
 *   default_widget = "radioactivity_reference",
 *   default_formatter = "radioactivity_reference_emitter",
 *   cardinality = 1,
 * )
 */
class RadioactivityReferenceItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'target_type' => 'radioactivity',
      'profile' => 'decay',
      'halflife' => 60 * 60 * 12,
      'granularity' => 60 * 15,
      'cutoff' => 1,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'default_energy' => 0,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function getPreconfiguredOptions() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['energy'] = DataDefinition::create('float')
      ->setLabel(new TranslatableMarkup('Energy level'))
      ->setDescription(new TranslatableMarkup('The radioactivity energy level'))
      ->setComputed(TRUE)
      ->setReadOnly(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = parent::storageSettingsForm($form, $form_state, $has_data);
    $this->addReferenceFieldWarning($form);

    $elements['target_type']['#access'] = FALSE;

    $elements['profile'] = [
      '#type' => 'radios',
      '#title' => $this->t('Energy profile'),
      '#default_value' => $this->getSetting('profile'),
      '#required' => TRUE,
      '#options' => [
        'count' => 'Count',
        'linear' => 'Linear',
        'decay' => 'Decay',
      ],
      '#description' => $this->t('Count: Energy increases by 1 with each view. Never decreases.<br/>
Linear: Energy increases by the emission amount. Decreases by 1 per second.<br/>
Decay: Energy increases by the emission amount. Decreases 50% per half-life time.'),
    ];

    $elements['granularity'] = [
      '#type' => 'number',
      '#title' => $this->t('Granularity'),
      '#min' => 1,
      '#default_value' => $this->getSetting('granularity'),
      '#description' => $this->t('The time in seconds that the energy levels are kept before applying the decay.'),
      '#states' => [
        'visible' => [
          'input[name="settings[profile]"]' => [
            ['value' => 'linear'],
            ['value' => 'decay'],
          ],
        ],
      ],
    ];

    $elements['halflife'] = [
      '#type' => 'number',
      '#title' => $this->t('Half-life time'),
      '#min' => 1,
      '#default_value' => $this->getSetting('halflife'),
      '#description' => $this->t('The time in seconds in which the energy level halves.'),
      '#states' => [
        'visible' => [
          'input[name="settings[profile]"]' => ['value' => 'decay'],
        ],
      ],
    ];

    $elements['cutoff'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cutoff'),
      '#pattern' => '[0-9]+(\.[0-9]+)?',
      '#size' => 20,
      '#default_value' => $this->getSetting('cutoff'),
      '#description' => $this->t('Energy levels under this value is set to zero. Example: 0.5, 2.'),
      '#states' => [
        'invisible' => [
          'input[name="settings[profile]"]' => ['value' => 'count'],
        ],
      ],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $field = $form_state->getFormObject()->getEntity();
    $form = parent::fieldSettingsForm($form, $form_state);
    $this->addReferenceFieldWarning($form);

    $form['handler']['#access'] = FALSE;

    $form['default_energy'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default energy'),
      '#description' => $this->t('The default energy value for this field, used when creating new content.'),
      '#required' => TRUE,
      '#default_value' => $field->getSetting('default_energy'),
      '#pattern' => '[0-9]+(\.[0-9]+)?',
      '#size' => 20,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $needsSave = FALSE;

    $requestTime = \Drupal::time()->getRequestTime();

    // New entity: Store the energy value in the entity. Saving the entity is
    // handled by parent::preSave.
    if ($this->hasNewEntity()) {
      $this->entity->setEnergy($this->energy);
      $this->entity->setTimestamp($requestTime);
    }
    // Existing entity: Store the energy value when it has changed.
    elseif ($this->energy != $this->initial_energy) {
      $this->entity->setEnergy($this->energy);
      $this->entity->setTimestamp($requestTime);
      $needsSave = TRUE;
    }

    // Keep the language code of the radioactivity entity in sync with the
    // language code of the host.
    if ($this->getLangcode() !== $this->entity->getLangcode) {
      $this->entity->setLangcode($this->getLangcode());
      if (!$this->hasNewEntity()) {
        $needsSave = TRUE;
      }
    }

    if ($needsSave) {
      $this->entity->save();
    }

    parent::preSave();
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {

    if ($this->entity) {
      $this->entity->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {

    $entity_storage = \Drupal::entityTypeManager()->getStorage('radioactivity');
    $entity = $entity_storage->createWithSampleValues('radioactivity');
    return [
      'entity' => $entity,
      'energy' => $entity->getEnergy(),
    ];
  }

  /**
   * Adds a warning message to the form about updating existing data.
   *
   * The message is added if the field is being added and if data of this entity
   * type exists in the database.
   *
   * @param array $form
   *   The form to add the message to.
   */
  private function addReferenceFieldWarning(array &$form) {

    if ($this->fieldDataIsMissing()) {
      $form['#prefix'] = '<div class="messages messages--warning">' . $this->t('There is data for this entity in the database. After adding this field, you must generate Radioactivity entities and update the radioactivity reference fields. For more information, see the <a href=":docs">online documentation</a>.', [':docs' => 'https://www.drupal.org/docs/contributed-modules/radioactivity']) . '</div>';
    }
  }

  /**
   * Determine if entities of this type and bundle exists but no field data.
   *
   * @return bool
   *   True if so.
   */
  private function fieldDataIsMissing(): bool {
    $entity = $this->getEntity();
    $entityStorage = \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId());

    $query = $entityStorage->getQuery()->count();
    $bundleKey = $entity->getEntityType()->getKey('bundle');
    if ($bundleKey) {
      $query->condition($bundleKey, $entity->bundle());
    }

    $hasEntityData = $query->execute() > 0;
    if (!$hasEntityData) {
      return FALSE;
    }

    // When we get here, data of this entity_type:bundle exists. Continue
    // checking if data for this field already exists.
    $fieldName = $this->getParent()->getName();
    $query->exists($fieldName);
    $hasFieldData = $query->execute() > 0;

    return !$hasFieldData;
  }

}
