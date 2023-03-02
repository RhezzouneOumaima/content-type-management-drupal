<?php

namespace Drupal\radioactivity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Defines a service for radioactivity #lazy_builder callbacks.
 */
class RadioactivityLazyBuilder implements TrustedCallbackInterface {

  /**
   * The radioactivity entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $radioactivityStorage;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->radioactivityStorage = $entity_type_manager->getStorage('radioactivity');
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['buildReferencedValue'];
  }

  /**
   * A #lazy_builder callback: The value of a referenced radioactivity entity.
   *
   * @param int $entityId
   *   The Radioactivity entity ID.
   * @param int|null $decimals
   *   The number of decimals to display the value width. By default the raw
   *   float value is displayed.
   * @param string $decimalSeparator
   *   Decimal separator.
   * @param string $thousandsSeparator
   *   Thousands separator.
   *
   * @return array
   *   Drupal render array.
   */
  public function buildReferencedValue(int $entityId, ?int $decimals = NULL, string $decimalSeparator = '.', string $thousandsSeparator = ','): array {

    /** @var \Drupal\radioactivity\RadioactivityInterface $entity */
    $entity = $this->radioactivityStorage->load($entityId);
    if (empty($entity)) {
      return [];
    }

    if (is_null($decimals)) {
      $value = $entity->getEnergy();
    }
    else {
      $value = number_format($entity->getEnergy(), $decimals, $decimalSeparator, $thousandsSeparator);
    }

    return [
      '#markup' => $value,
      '#cache' => ['tags' => $entity->getCacheTags()],
    ];
  }

}
