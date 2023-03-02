<?php

namespace Drupal\radioactivity;

/**
 * Defines the Radioactivity Incident.
 */
interface IncidentInterface {

  /**
   * Test validity of the Incident.
   *
   * @return bool
   *   True if the incident is valid. False if not.
   */
  public function isValid(): bool;

  /**
   * Convert to JSON format.
   *
   * @return string
   *   Json encoded incident data.
   */
  public function toJson(): string;

  /**
   * Returns the incident field name.
   *
   * @return string
   *   The incident field name.
   */
  public function getFieldName(): string;

  /**
   * Returns the incident entity type.
   *
   * @return string
   *   The incident entity type.
   */
  public function getEntityTypeId(): string;

  /**
   * Returns the incident entity id.
   *
   * @return string|int
   *   The incident entity id.
   */
  public function getEntityId();

  /**
   * Returns the id of the referenced radioactivity entity.
   *
   * @return string|int
   *   The target id.
   */
  public function getTargetId();

  /**
   * Returns the incident energy.
   *
   * @return int|float
   *   The incident energy.
   */
  public function getEnergy();

}
