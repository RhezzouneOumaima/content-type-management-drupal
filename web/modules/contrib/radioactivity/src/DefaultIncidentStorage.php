<?php

namespace Drupal\radioactivity;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Database\Connection;

/**
 * Defines a default incident storage.
 */
class DefaultIncidentStorage implements IncidentStorageInterface {

  /**
   * The table name.
   */
  const TABLE_NAME = 'radioactivity_incident';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The serialization class to use.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * DefaultIncidentStorage constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The serialization class to use.
   */
  public function __construct(Connection $connection, SerializationInterface $serializer) {
    $this->connection = $connection;
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public function addIncident(IncidentInterface $incident) {
    $this->connection->insert(self::TABLE_NAME)
      ->fields([
        'incident' => $this->serializer->encode($incident),
        'entity_type' => $incident->getEntityTypeId(),
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   *   Throws exception when an unexpected database error occurs.
   */
  public function getIncidents(): array {

    $result = $this->connection->select(self::TABLE_NAME, 'ri')
      ->fields('ri', ['iid', 'incident'])
      ->orderBy('ri.iid', 'ASC')
      ->execute();

    $values = [];
    foreach ($result as $item) {
      if ($item) {
        $values[] = $this->serializer->decode($item->incident);
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   *   Throws exception when an unexpected database error occurs.
   */
  public function getIncidentsByType(string $entity_type = ''): array {

    $query = $this->connection->select(self::TABLE_NAME, 'ri');
    $query->fields('ri', ['iid', 'incident', 'entity_type']);
    $query->orderBy('ri.iid', 'ASC');
    if ($entity_type) {
      $query->condition('entity_type', $entity_type);
    }
    $result = $query->execute();

    $incidents = [];
    foreach ($result as $item) {
      /** @var \Drupal\radioactivity\IncidentInterface $incident */
      $incident = $this->serializer->decode($item->incident);
      $incidents[$incident->getEntityTypeId()][$incident->getEntityId()][] = $incident;
    }

    return $incidents ?: [[]];
  }

  /**
   * {@inheritdoc}
   */
  public function clearIncidents() {
    $this->connection
      ->truncate(self::TABLE_NAME)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function injectSettings(array &$page) {
    global $base_url;
    $page['#attached']['drupalSettings']['radioactivity']['type'] = 'default';
    $page['#attached']['drupalSettings']['radioactivity']['endpoint'] = $base_url . '/radioactivity/emit';
  }

}
