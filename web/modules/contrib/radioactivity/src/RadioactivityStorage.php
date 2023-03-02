<?php

namespace Drupal\radioactivity;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines the storage handler class for radioactivity entities.
 */
class RadioactivityStorage extends SqlContentEntityStorage {

  /**
   * {@inheritdoc}
   */
  public function createWithSampleValues($bundle = FALSE, array $values = []) {

    // Only allow a limited set of field names.
    $values = array_intersect_key($values, array_flip([
      'energy',
      'timestamp',
      'langcode',
    ]));

    $values += [
      'energy' => rand(1, 100),
      'timestamp' => time(),
    ];

    return $this->create($values);
  }

}
