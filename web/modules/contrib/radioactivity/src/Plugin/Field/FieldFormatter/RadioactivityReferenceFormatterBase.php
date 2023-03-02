<?php

namespace Drupal\radioactivity\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

/**
 * Parent plugin for radioactivity entity reference formatters.
 */
abstract class RadioactivityReferenceFormatterBase extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected function getEntitiesToView(EntityReferenceFieldItemListInterface $items, $langcode) {
    $entities = [];

    foreach ($items as $delta => $item) {
      // Ignore items where no entity could be loaded in prepareView().
      if (empty($item->_loaded)) {
        continue;
      }

      $entity = $item->entity;

      // Add the referring item, in case the formatter needs it.
      $entity->_referringItem = $items[$delta];
      $entities[$delta] = $entity;
    }

    return $entities;
  }

}
