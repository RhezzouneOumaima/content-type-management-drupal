<?php

namespace Drupal\radioactivity;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\radioactivity\Entity\Radioactivity;

/**
 * Service for updating Radioactivity reference fields.
 */
class RadioactivityReferenceUpdater implements RadioactivityReferenceUpdaterInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $dateTime;

  /**
   * Local storage for field map of radioactivity_reference fields.
   *
   * @var array|null
   */
  protected ?array $allReferenceFields = NULL;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Component\Datetime\TimeInterface $dateTime
   *   The time service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, TimeInterface $dateTime) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->dateTime = $dateTime;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMissingReferences(): bool {

    return count($this->getReferencesWithoutTarget()) > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferencesWithoutTarget(): array {

    $result = [];
    $fieldNames = $this->getAllReferenceFields();

    foreach ($fieldNames as $entityType => $bundles) {
      foreach ($bundles as $bundle => $fields) {
        $ids = $this->entitiesWithNonexistentFields($entityType, $bundle, $fields);
        $result = $this->mergeEntityIds($result, $entityType, $ids);
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function updateReferenceFields(FieldableEntityInterface $entity): bool {

    // Ignore entities that do not contain a radioactivity reference field.
    $fieldNames = $this->getReferenceFields($entity->getEntityTypeId(), $entity->bundle());
    $entityIsUpdated = FALSE;

    foreach ($fieldNames as $fieldName) {
      $radioactiveReferenceField = $entity->get($fieldName);
      if ($radioactiveReferenceField->isEmpty()) {

        // Create a radioactivity entity as target.
        $radioactivityEntity = $this->createRadioactivity(
          $this->getRequestTime(),
          $this->getFieldDefaultEnergy($entity, $fieldName),
          $radioactiveReferenceField->getLangcode()
        );

        $radioactiveReferenceField->setValue($radioactivityEntity);
        $entityIsUpdated = TRUE;
      }
    }

    if ($entityIsUpdated) {
      $entity->save();
    }

    return $entityIsUpdated;
  }

  /**
   * Returns IDs of entities where one or more fields have no value.
   *
   * @param string $entityType
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   * @param array $fields
   *   The entity reference field names to check.
   *
   * @return array
   *   Array of entity IDs. Empty array if none were found.
   */
  protected function entitiesWithNonexistentFields(string $entityType, string $bundle, array $fields): array {

    $entityStorage = $this->entityTypeManager->getStorage($entityType);
    $bundleKey = $this->entityTypeManager->getDefinition($entityType)
      ->getKey('bundle');

    $query = $entityStorage->getQuery();
    if ($bundleKey) {
      $query->condition($bundleKey, $bundle);
    }

    $orGroup = $query->orConditionGroup();
    foreach ($fields as $field) {
      $orGroup->notExists($field);
    }
    $query->condition($orGroup);

    return $query->execute();
  }

  /**
   * Returns the names of radioactivity reference fields of given entity data.
   *
   * @param string $entityType
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   *
   * @return array
   *   The field names. Empty array if this entity does not contain
   *   radioactivity reference fields.
   */
  protected function getReferenceFields(string $entityType, string $bundle): array {

    $fields = $this->getAllReferenceFields();
    return $fields[$entityType][$bundle] ?? [];
  }

  /**
   * Returns a map of radioactivity reference fields per entity and bundle.
   *
   * Uses the EntityFieldManagerInterface::getFieldMapByFieldType but changes
   * the array structure for easy of use with given entity type and bundle.
   *
   * @return array
   *   Array of radioactivity_reference fields. Structure:
   *   - entity type: array keyed by bundle.
   *     - bundle: array of field names.
   */
  protected function getAllReferenceFields(): array {

    if (!is_null($this->allReferenceFields)) {
      return $this->allReferenceFields;
    }

    $fieldMap = $this->entityFieldManager
      ->getFieldMapByFieldType('radioactivity_reference');

    $this->allReferenceFields = [];
    foreach ($fieldMap as $entityType => $fields) {
      foreach ($fields as $fieldName => $data) {
        foreach ($data['bundles'] as $bundle) {
          $this->allReferenceFields[$entityType][$bundle][] = $fieldName;
        }
      }
    }

    return $this->allReferenceFields;
  }

  /**
   * Creates a radioactivity entity.
   *
   * @param int $timestamp
   *   The timestamp.
   * @param float|int $energy
   *   The energy.
   * @param string $langcode
   *   The language code.
   *
   * @return \Drupal\radioactivity\RadioactivityInterface
   *   The created entity.
   */
  protected function createRadioactivity(int $timestamp, $energy, string $langcode): RadioactivityInterface {

    $radioactivityEntity = Radioactivity::create([
      'timestamp' => $timestamp,
      'energy' => $energy,
      'langcode' => $langcode,
    ]);
    $radioactivityEntity->save();

    return $radioactivityEntity;
  }

  /**
   * Merges entity IDs of multiple entity types.
   *
   * @param array $data
   *   The existing entity IDs.
   * @param string $entityType
   *   The entity type of the IDs.
   * @param array $ids
   *   The IDs to merge.
   *
   * @return array
   *   The merged data. Associative array keyed by "entity_type:entity_id".
   *   Values a structured array:
   *   - entity_type: The entity type.
   *   - id: The entity ID.
   */
  protected function mergeEntityIds(array $data, string $entityType, array $ids): array {

    foreach ($ids as $id) {
      $data["$entityType:$id"] = [
        'entity_type' => $entityType,
        'id' => $id,
      ];
    }

    return $data;
  }

  /**
   * Returns the timestamp for the current request.
   *
   * @return int
   *   The timestamp.
   */
  protected function getRequestTime(): int {
    return $this->dateTime->getRequestTime();
  }

  /**
   * Returns the configured default energy of a field.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity where the field is located.
   * @param string $fieldName
   *   The field for which to ghe the value.
   *
   * @return float
   *   The configured default energy.
   */
  protected function getFieldDefaultEnergy(FieldableEntityInterface $entity, string $fieldName): float {
    return $entity->getFieldDefinition($fieldName)->getSetting('default_energy');
  }

}
