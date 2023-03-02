<?php

namespace Drupal\radioactivity;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the aggregator feed entity type.
 */
class RadioactivityViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData(): array {
    $data = parent::getViewsData();

    $data['radioactivity']['langcode']['title'] = $this->t('Language');

    // The radioactivity entity does not provide an entity view.
    unset($data['radioactivity']['rendered_entity']);

    return $data;
  }

}
