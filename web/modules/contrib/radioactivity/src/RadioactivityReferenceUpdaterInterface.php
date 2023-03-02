<?php

namespace Drupal\radioactivity;

use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Interface for RadioactivityReferenceUpdater.
 */
interface RadioactivityReferenceUpdaterInterface {

  /**
   * Checks if Radioactivity reference fields exist without a referenced entity.
   *
   * @return bool
   *   True if so.
   */
  public function hasMissingReferences(): bool;

  /**
   * Returns entities with radioactivity reference field(s) without target.
   *
   * @return array
   *   Structured array of entity type and IDs. Associative array keyed by
   *   "entity_type:entity_id". Values a structured array:
   *   - entity_type: The entity type.
   *   - id: The entity ID.
   */
  public function getReferencesWithoutTarget(): array;

  /**
   * Adds missing radioactivity entities to radioactivity reference fields.
   *
   * Typical of this method is when a radioactivity reference field is added
   * to a content type of existing content. The field is created, but the
   * entities do not yet reference radioactivity entities. Calling this method
   * for each entity will create the missing Radioactivity entity and the
   * corresponding reference.
   *
   * Note that the entity may be (re-)saved during this process.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to use.
   *
   * @return bool
   *   True if the entity has been updated.
   */
  public function updateReferenceFields(FieldableEntityInterface $entity): bool;

}
