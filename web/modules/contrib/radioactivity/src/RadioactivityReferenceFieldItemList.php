<?php

namespace Drupal\radioactivity;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a item list class for radioactivity reference fields.
 */
class RadioactivityReferenceFieldItemList extends EntityReferenceFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function defaultValuesForm(array &$form, FormStateInterface $form_state): array {
    // @todo Find a way to use the default value widget for Energy. The Energy
    // field is a computed field, and I failed to get save the default value
    // using the default values widget.
    return [];
  }

}
