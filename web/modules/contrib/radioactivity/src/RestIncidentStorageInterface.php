<?php

namespace Drupal\radioactivity;

/**
 * Defines the incident storage interface.
 */
interface RestIncidentStorageInterface extends IncidentStorageInterface {

  /**
   * Sets the REST endpoint URL.
   *
   * @param string|null $endpoint
   *   The endpoint URL to override the default. Null to return to the default.
   */
  public function setEndpoint(?string $endpoint = NULL);

}
