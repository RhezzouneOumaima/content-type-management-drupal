<?php

namespace Drupal\radioactivity;

use Drupal\Core\Site\Settings;
use Drupal\Component\Serialization\Json;

/**
 * Data class for Radioactivity Incident.
 *
 * @package Drupal\radioactivity
 */
class Incident implements IncidentInterface {

  /**
   * The incident field name.
   *
   * @var string
   */
  private string $fieldName;

  /**
   * The incident entity type.
   *
   * @var string
   */
  private string $entityType;

  /**
   * The incident entity id.
   *
   * @var string|int
   */
  private $entityId;

  /**
   * The id of the referenced radioactivity entity.
   *
   * @var string|int
   */
  private $targetId;

  /**
   * The incident energy.
   *
   * @var int|float
   */
  private $energy;

  /**
   * The incident hash.
   *
   * @var string|null
   */
  private ?string $hash;

  /**
   * Constructor.
   *
   * @param string $field_name
   *   The field name from the incident.
   * @param string $entity_type
   *   The entity type from the incident.
   * @param string|int $entity_id
   *   The entity id from the incident.
   * @param string|int $target_id
   *   The id from the referenced radioactivity entity.
   * @param int|float $energy
   *   The energy from the incident.
   * @param string|null $hash
   *   The hash from the incident.
   */
  public function __construct(string $field_name, string $entity_type, $entity_id, $target_id, $energy, ?string $hash = NULL) {
    $this->fieldName  = $field_name;
    $this->entityType = $entity_type;
    $this->entityId   = $entity_id;
    $this->targetId   = $target_id;
    $this->energy     = $energy;
    $this->hash       = $hash;
  }

  /**
   * {@inheritdoc}
   */
  public function isValid(): bool {
    return strcmp($this->hash, $this->calculateHash()) === 0;
  }

  /**
   * Calculate hash for this incident.
   *
   * @return string
   *   The calculated hash of this incident.
   */
  private function calculateHash(): string {
    return sha1(implode('##', [
      $this->fieldName,
      $this->entityType,
      $this->entityId,
      $this->targetId,
      $this->energy,
      Settings::getHashSalt(),
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function toJson(): string {
    return Json::encode([
      'fn' => $this->fieldName,
      'et' => $this->entityType,
      'id' => $this->entityId,
      'ti' => $this->targetId,
      'e' => $this->energy,
      'h' => $this->calculateHash(),
    ]);
  }

  /**
   * Create an Incident from data received in an http request.
   *
   * @param array $data
   *   Associative array of incident data.
   *
   * @return \Drupal\radioactivity\IncidentInterface
   *   An Incident object.
   */
  public static function createFromPostData(array $data) {
    $data += [
      'fn' => '',
      'et' => '',
      'id' => '',
      'ti' => 0,
      'e' => 0,
      'h' => '',
    ];
    return new Incident($data['fn'], $data['et'], $data['id'], $data['ti'], $data['e'], $data['h']);
  }

  /**
   * Create an Incident from field items, an item within it and a formatter.
   *
   * @param object $items
   *   The items containing item.
   * @param object $item
   *   The item in question.
   * @param object $formatter
   *   The formatter in use.
   *
   * @return \Drupal\radioactivity\IncidentInterface
   *   The incident object.
   */
  public static function createFromFieldItemsAndFormatter(object $items, object $item, object $formatter) {
    return new Incident(
      $items->getName(),
      $item->getEntity()->getEntityTypeId(),
      $item->getEntity()->id(),
      $item->target_id ?? 0,
      $formatter->getSetting('energy')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldName(): string {
    return $this->fieldName;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId(): string {
    return $this->entityType;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityId() {
    return $this->entityId;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetId() {
    return $this->targetId;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnergy() {
    return $this->energy;
  }

}
