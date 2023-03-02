<?php

namespace Drupal\radioactivity\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\radioactivity\Entity\Radioactivity;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'radioactivity_reference' widget.
 *
 * @FieldWidget(
 *   id = "radioactivity_reference",
 *   label = @Translation("Radioactivity"),
 *   field_types = {
 *     "radioactivity_reference"
 *   }
 * )
 */
class RadioactivityReferenceWidget extends WidgetBase {

  /**
   * Indicates whether the current widget instance is in translation.
   *
   * @var bool
   */
  protected ?bool $isTranslating = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ElementInfoManagerInterface $element_info) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->elementInfo = $element_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['third_party_settings'], $container->get('element_info'));
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $host = $items->getEntity();
    $this->initIsTranslating($form_state, $host);

    $hasTargetEntity = !empty($items[$delta]->target_id) && $items[$delta]->entity;

    // The energy value is stored in the attached radioactivity entity. It is
    // copied from there so it can be managed by the user via the interface.
    $defaultEnergy = $host->getFieldDefinition($items->getName())->getSetting('default_energy');
    if ($hasTargetEntity) {
      $defaultEnergy = $items[$delta]->entity->getEnergy();
    }

    /** @var \Drupal\radioactivity\RadioactivityInterface $radioactivityEntity */
    $radioactivityEntity = $hasTargetEntity ? $items[$delta]->entity : Radioactivity::create([
      'energy' => $defaultEnergy,
    ]);

    if ($this->isTranslating && $items->getFieldDefinition()->isTranslatable()) {
      // If the field is being translated, the translated host entity should
      // refer to a different entity. The current one is replaced by a
      // duplicate with the field's translation language.
      if (!empty($form_state->get('content_translation'))) {
        $radioactivityEntity = $radioactivityEntity->createDuplicate();
        $radioactivityEntity->setLangcode($form_state->get('langcode'));
        $hasTargetEntity = FALSE;
      }
    }
    else {
      $radioactivityEntity->setLangcode($form_state->get('langcode'));
    }

    $elements = [];
    $elements['energy'] = [
      '#type' => 'textfield',
      '#pattern' => '[0-9]+(\.[0-9]+)?',
      '#default_value' => $defaultEnergy,
    ] + $element;

    $elements['initial_energy'] = [
      '#type' => 'value',
      '#value' => $defaultEnergy,
    ];

    $elements['entity'] = [
      '#type' => 'value',
      '#value' => $radioactivityEntity,
    ];

    if ($hasTargetEntity) {
      $elements['target_id'] = [
        '#type' => 'value',
        '#default_value' => $items[$delta]->target_id,
      ];
    }

    if (!isset($form['advanced'])) {
      return $elements;
    }

    // Put the form elements into the form's "advanced" group.
    $elements += [
      '#type' => 'details',
      '#title' => $elements['energy']['#title'],
      '#group' => 'advanced',
      '#required' => TRUE,
      '#weight' => 40,
    ];

    return $elements;
  }

  /**
   * Initializes the translation form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   * @param \Drupal\Core\Entity\EntityInterface $host
   *   Host entity of the field.
   */
  protected function initIsTranslating(FormStateInterface $form_state, EntityInterface $host) {
    if ($this->isTranslating != NULL) {
      return;
    }
    $this->isTranslating = FALSE;
    if (!$host->isTranslatable()) {
      return;
    }
    if (!$host->getEntityType()->hasKey('default_langcode')) {
      return;
    }
    $default_langcode_key = $host->getEntityType()->getKey('default_langcode');
    if (!$host->hasField($default_langcode_key)) {
      return;
    }

    if (!empty($form_state->get('content_translation'))) {
      // Adding a language through the ContentTranslationController.
      $this->isTranslating = TRUE;
    }
    if ($host->hasTranslation($form_state->get('langcode')) && $host->getTranslation($form_state->get('langcode'))->get($default_langcode_key)->value == 0) {
      // Editing a translation.
      $this->isTranslating = TRUE;
    }
  }

}
