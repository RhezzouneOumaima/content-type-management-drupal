<?php

namespace Drupal\radioactivity;

use Drupal\Component\Serialization\Json;

/**
 * Defines a REST incident storage.
 */
class RestIncidentStorage implements RestIncidentStorageInterface {

  /**
   * REST endpoint URL for incidents.
   *
   * @var string|null
   */
  protected ?string $endpoint = NULL;

  /**
   * {@inheritdoc}
   */
  public function addIncident(IncidentInterface $incident) {
    throw new \Exception("The Radioactivity rest endpoint expects incidents to be added somewhere else.");
  }

  /**
   * {@inheritdoc}
   */
  public function getIncidents(): array {
    $data = $this->getIncidentsFromStorage();
    $result = [];
    foreach ($data as $rows) {
      foreach ($rows as $row) {
        $incident = Incident::createFromPostData($row);
        if ($incident->isValid()) {
          $result[] = $incident;
        }
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getIncidentsByType($entity_type = ''): array {
    $incidents = [];

    $stored_incidents = $this->getIncidents();
    foreach ($stored_incidents as $incident) {
      $incidents[$incident->getEntityTypeId()][$incident->getEntityId()][] = $incident;
    }

    if (isset($incidents[$entity_type])) {
      return [$entity_type => $incidents[$entity_type]];
    }

    return $incidents ?: [[]];
  }

  /**
   * {@inheritdoc}
   */
  public function clearIncidents() {
    $this->clearIncidentStorage();
  }

  /**
   * {@inheritdoc}
   */
  public function injectSettings(array &$page) {
    $page['#attached']['drupalSettings']['radioactivity']['type'] = 'rest';
    $page['#attached']['drupalSettings']['radioactivity']['endpoint'] = $this->getEndpoint();
  }

  /**
   * {@inheritdoc}
   */
  public function setEndpoint(?string $endpoint = NULL) {
    $this->endpoint = $endpoint;
  }

  /**
   * Returns the endpoint URL.
   *
   * @return string
   *   The endpoint URL.
   */
  protected function getEndpoint(): string {
    if (is_null($this->endpoint)) {
      $this->endpoint = $this->getDefaultEndpoint();
    }

    return $this->endpoint;
  }

  /**
   * Returns the default storage endpoint.
   *
   * @return string
   *   The storage endpoint.
   */
  protected function getDefaultEndpoint(): string {
    global $base_url;

    return $base_url . '/' . \Drupal::service('extension.list.module')->getPath('radioactivity') . '/endpoints/file/rest.php';
  }

  /**
   * Returns all incidents from the storage.
   *
   * @return array
   *   The incidents.
   */
  protected function getIncidentsFromStorage(): array {
    return Json::decode(file_get_contents("{$this->getEndpoint()}?get"));
  }

  /**
   * Deletes all incidents from the storage.
   */
  protected function clearIncidentStorage() {
    file_get_contents("{$this->getEndpoint()}?clear");
  }

}
