<?php

namespace Drupal\radioactivity;

/**
 * Defines the incident storage interface.
 */
interface IncidentStorageInterface {

  /**
   * The key to identify the radioactivity incident storage.
   *
   * @deprecated in radioactivity:4.0.0-beta2 and is removed from
   *   radioactivity:4.0.0.
   */
  const STORAGE_KEY = 'radioactivity_incidents';

  /**
   * Adds an incident to the storage.
   *
   * @param \Drupal\radioactivity\IncidentInterface $incident
   *   The incident object.
   */
  public function addIncident(IncidentInterface $incident);

  /**
   * Gets all incidents from the storage.
   *
   * @return \Drupal\radioactivity\IncidentInterface[]
   *   Array of incident objects.
   */
  public function getIncidents(): array;

  /**
   * Gets all incidents from the storage per entity type.
   *
   * @param string $entity_type
   *   Entity type for selection. Default to all entity types.
   *
   * @return \Drupal\radioactivity\IncidentInterface[][]
   *   Array of incident objects keyed by entity type (1st) and entity ID (2nd).
   */
  public function getIncidentsByType(string $entity_type = ''): array;

  /**
   * Clears the incident storage.
   */
  public function clearIncidents();

  /**
   * Add endpoint settings to the page.
   *
   * @param array $page
   *   Page attachments as provided by hook_page_attachments_alter().
   */
  public function injectSettings(array &$page);

}
