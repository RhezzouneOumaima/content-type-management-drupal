<?php

namespace Drupal\radioactivity;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\radioactivity\Event\EnergyBelowCutoffEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Processes Radioactivity incidents and and energy decay.
 *
 * @package Drupal\radioactivity
 */
class RadioactivityProcessor implements RadioactivityProcessorInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The state key-value storage.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * The radioactivity logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $log;

  /**
   * The radioactivity storage.
   *
   * @var \Drupal\radioactivity\IncidentStorageInterface
   */
  protected IncidentStorageInterface $storage;

  /**
   * The timestamp for the current request.
   *
   * @var int
   */
  protected int $requestTime;

  /**
   * The queue factory service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected QueueFactory $queue;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * Constructs a Radioactivity processor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The key-value storage.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger.
   * @param \Drupal\radioactivity\StorageFactory $storage
   *   The storage factory service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Queue\QueueFactory $queue
   *   The queue factory service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, StateInterface $state, LoggerChannelFactoryInterface $logger_factory, StorageFactory $storage, TimeInterface $time, QueueFactory $queue, EventDispatcherInterface $event_dispatcher) {
    $this->entityTypeManager = $entity_type_manager;
    $this->state = $state;
    $this->log = $logger_factory->get(self::LOGGER_CHANNEL);
    $this->storage = $storage->getConfiguredStorage();
    $this->requestTime = $time->getRequestTime();
    $this->queue = $queue;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function processDecay(): int {

    $raCount = $this->processRadioactivityDecay();
    $raRefCount = $this->processRadioactivityReferenceDecay();

    // If no Radioactivity fields are configured, both counts are false and
    // there is need to report anything.
    if ($raCount === FALSE && $raRefCount === FALSE) {
      // @todo Set watchdog message to suggest disabling the RA module.
      return 0;
    }

    $this->state->set(self::LAST_PROCESSED_STATE_KEY, $this->requestTime);

    $raCount = $raCount ?: 0;
    $raRefCount = $raRefCount ?: 0;
    $resultCount = $raCount + $raRefCount;
    $this->log->notice('Processed @count radioactivity decays.', ['@count' => $resultCount]);

    return $resultCount;
  }

  /**
   * Process decay of 'radioactivity' type field.
   *
   * @return false|int
   *   The number of processed decays. False if no fields were found.
   */
  private function processRadioactivityDecay() {
    $resultCount = 0;

    $fieldConfigs = $this->getRadioactivityFieldsConfig('radioactivity');
    if (!$fieldConfigs) {
      return FALSE;
    }

    foreach ($fieldConfigs as $fieldConfig) {
      $profile = $fieldConfig->getSetting('profile');
      if ($fieldConfig->hasData()
        && ($profile === 'linear' || $profile === 'decay')
        && $this->hasReachedGranularityThreshold($fieldConfig)
      ) {
        $resultCount += $this->processFieldDecay($fieldConfig, FALSE);
      }
    }

    return $resultCount;
  }

  /**
   * Process decay of 'radioactivity_reference' type field.
   *
   * @return false|int
   *   The number of processed decays. False if no fields were found.
   */
  private function processRadioactivityReferenceDecay() {
    $resultCount = 0;

    $fieldConfigs = $this->getRadioactivityFieldsConfig('radioactivity_reference');
    if (!$fieldConfigs) {
      return FALSE;
    }

    foreach ($fieldConfigs as $fieldConfig) {
      $profile = $fieldConfig->getSetting('profile');
      if ($fieldConfig->hasData()
        && ($profile === 'linear' || $profile === 'decay')
        && $this->hasReachedGranularityThreshold($fieldConfig)
      ) {
        $resultCount += $this->processFieldDecay($fieldConfig, TRUE);
      }
    }

    return $resultCount;
  }

  /**
   * Returns the configuration of Radioactivity fields.
   *
   * These are the fields, across all entity types, that are configured
   * for radioactivity.
   *
   * @param string $type
   *   The type of fields to get.
   *
   * @return \Drupal\field\FieldStorageConfigInterface[]
   *   The configurations.
   */
  protected function getRadioactivityFieldsConfig(string $type): array {

    /** @var \Drupal\field\Entity\FieldStorageConfig[] $fieldConfigs */
    $fieldConfigIds = $this->entityTypeManager
      ->getStorage('field_storage_config')
      ->getQuery()
      ->condition('type', $type)
      ->execute();

    if (empty($fieldConfigIds)) {
      return [];
    }

    /** @var \Drupal\field\FieldStorageConfigInterface[] $configs */
    $configs = $this->entityTypeManager
      ->getStorage('field_storage_config')
      ->loadMultiple($fieldConfigIds);

    return $configs;
  }

  /**
   * Determines if the field has reached the next granularity threshold.
   *
   * For linear and decay profile types, we calculate the decay after x seconds
   * have passed since the last cron run. The number of seconds is stored in
   * 'granularity' field setting.
   *
   * @param \Drupal\field\FieldStorageConfigInterface $fieldConfig
   *   Configuration of the field to be checked.
   *
   * @return bool
   *   True if the threshold was reached.
   */
  private function hasReachedGranularityThreshold(FieldStorageConfigInterface $fieldConfig): bool {
    $granularity = $fieldConfig->getSetting('granularity');
    if ($granularity == 0) {
      return TRUE;
    }

    $lastCronTimestamp = $this->state->get(self::LAST_PROCESSED_STATE_KEY, 0);
    $threshold = $lastCronTimestamp - ($lastCronTimestamp % $granularity) + $granularity;
    return $this->requestTime >= $threshold;
  }

  /**
   * Update entities attached to given field storage.
   *
   * @param \Drupal\field\FieldStorageConfigInterface $fieldConfig
   *   Configuration of the field to be processed.
   * @param bool $isReference
   *   The field is a radioactivity_reference field.
   *
   * @return int
   *   The number of processed entities.
   */
  private function processFieldDecay(FieldStorageConfigInterface $fieldConfig, bool $isReference = FALSE): int {
    $fieldName = $fieldConfig->get('field_name');
    $entityType = $fieldConfig->getTargetEntityTypeId();

    if ($isReference) {
      $query = $this->entityTypeManager->getStorage($entityType)->getQuery()
        ->condition($fieldName . '.entity', NULL, 'IS NOT NULL')
        ->condition($fieldName . '.entity.timestamp', $this->requestTime, ' <= ')
        ->condition($fieldName . '.entity.energy', 0, '>')
        ->AccessCheck(FALSE);
    }
    else {
      $query = $this->entityTypeManager->getStorage($entityType)->getQuery()
        ->condition($fieldName . '.timestamp', $this->requestTime, ' <= ')
        ->condition($fieldName . '.energy', NULL, 'IS NOT NULL')
        ->condition($fieldName . '.energy', 0, '>')
        ->AccessCheck(FALSE);
    }
    $entityIds = $query->execute();

    // Delegate processing to a queue worker to prevent memory errors when large
    // number of entities are processed.
    $chunks = array_chunk($entityIds, self::QUEUE_CHUNK_SIZE, TRUE);
    foreach ($chunks as $chunk) {
      $queue = $this->queue->get(self::QUEUE_WORKER_DECAY);
      $queue->createItem([
        'field_config' => $fieldConfig,
        'entity_ids' => $chunk,
      ]);
    }

    return count($entityIds);
  }

  /**
   * {@inheritdoc}
   */
  public function queueProcessDecay(FieldStorageConfigInterface $fieldConfig, array $entityIds) {
    $entityType = $fieldConfig->getTargetEntityTypeId();
    $fieldName = $fieldConfig->get('field_name');
    $profile = $fieldConfig->getSetting('profile');
    $halfLife = $fieldConfig->getSetting('halflife');
    $cutoff = $fieldConfig->getSetting('cutoff');

    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $entities */
    $entities = $this->entityTypeManager
      ->getStorage($entityType)
      ->loadMultiple($entityIds);

    foreach ($entities as $entity) {
      $languages = $entity->getTranslationLanguages();
      foreach ($languages as $language) {
        $entity = $entity->getTranslation($language->getId());

        // Do not decay energy for unpublished entities.
        if ($entity instanceof EntityPublishedInterface && !$entity->isPublished()) {
          continue;
        }

        $field = $entity->get($fieldName);
        $isReference = $field->getFieldDefinition()->getType() === 'radioactivity_reference';

        if ($isReference) {
          /** @var \Drupal\radioactivity\RadioactivityInterface $radioactivityEntity */
          $radioactivityEntity = $field->entity;
          if ($radioactivityEntity) {
            $newEnergy = $this->calculateEnergy($radioactivityEntity->getEnergy(), $radioactivityEntity->getTimestamp(), $profile, $halfLife);
            if ($this->energyBelowCutoff($newEnergy, $cutoff)) {
              $newEnergy = 0;
              $this->dispatchBelowCutoffEvent($entity);
            }

            $radioactivityEntity->setEnergy($newEnergy);
            $radioactivityEntity->setTimestamp($this->requestTime);
            $radioactivityEntity->save();
          }
        }
        else {
          $newEnergy = $this->calculateEnergy($field->energy, $field->timestamp, $profile, $halfLife);
          if ($this->energyBelowCutoff($newEnergy, $cutoff)) {
            $newEnergy = 0;
            $this->dispatchBelowCutoffEvent($entity);
          }

          // Set the new energy level and update the timestamp.
          $entity->get($fieldName)->setValue([
            'energy' => $newEnergy,
            'timestamp' => $this->requestTime,
          ]);

          if ($entity->getEntityType()->isRevisionable()) {
            $entity->setNewRevision(FALSE);
          }

          // Set flag so we can identify this entity save as one that just
          // updates the radioactivity value.
          $entity->radioactivityUpdate = TRUE;
          $entity->save();
        }
      }
    }
  }

  /**
   * Calculate energy value using decay profile data.
   *
   * @param float $energy
   *   The current energy value.
   * @param int $timestamp
   *   The last changed timestamp.
   * @param string $profile
   *   The profile type.
   * @param int $halfLife
   *   The half life time, in seconds.
   *
   * @return float
   *   The calculated energy value.
   */
  private function calculateEnergy(float $energy, int $timestamp, string $profile, int $halfLife): float {
    $result = $energy;

    $elapsed = $timestamp ? $this->requestTime - $timestamp : 0;

    switch ($profile) {
      case 'linear':
        $result = $energy > $elapsed ? $energy - $elapsed : 0;
        break;

      case 'decay':
        $result = $energy * pow(2, -$elapsed / $halfLife);
        break;
    }
    return $result;
  }

  /**
   * Check if energy is below the cutoff value.
   *
   * @param float $energy
   *   The energy value.
   * @param float $cutoff
   *   The energy cut-off value.
   *
   * @return bool
   *   True if below the cutoff.
   */
  private function energyBelowCutoff(float $energy, float $cutoff): bool {
    return $energy <= $cutoff;
  }

  /**
   * Dispatch event to notify that the energy fell below the cutoff value.
   *
   * This is needed for Rules integration, but can be used by any module that
   * wants to use events.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to use for the event.
   */
  private function dispatchBelowCutoffEvent(ContentEntityInterface $entity) {
    $event = new EnergyBelowCutoffEvent($entity);
    $this->eventDispatcher->dispatch($event, 'radioactivity_field_cutoff');
  }

  /**
   * {@inheritdoc}
   */
  public function processIncidents(): int {
    $resultCount = 0;

    $incidentsByType = $this->storage->getIncidentsByType();
    $this->storage->clearIncidents();

    foreach ($incidentsByType as $entityType => $incidents) {

      // Delegate processing to a queue worker to prevent memory errors when
      // large number of entities are processed.
      $chunks = array_chunk($incidents, self::QUEUE_CHUNK_SIZE, TRUE);
      foreach ($chunks as $chunk) {
        $queue = $this->queue->get(self::QUEUE_WORKER_INCIDENTS);
        $queue->createItem([
          'entity_type' => $entityType,
          'incidents' => $chunk,
        ]);
      }
      $resultCount += count($incidents);
    }

    $this->log->notice('Processed @count radioactivity incidents.', ['@count' => $resultCount]);
    return $resultCount;
  }

  /**
   * {@inheritdoc}
   */
  public function queueProcessIncidents(string $entityType, array $entityIncidents) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $entities */
    $entities = $this->entityTypeManager->getStorage($entityType)->loadMultiple(array_keys($entityIncidents));

    foreach ($entities as $entity) {
      $hostEntityUpdated = FALSE;
      /** @var \Drupal\radioactivity\RadioactivityInterface[] $referencedEntities */
      $referencedEntities = [];

      /** @var \Drupal\radioactivity\IncidentInterface $incident */
      foreach ($entityIncidents[$entity->id()] as $incident) {
        // Update the energy, but not the timestamp. The latter is used to
        // calculate decay and should not be updated when saving the incident
        // energy.
        if ($incident->getTargetId()) {
          if (!isset($referencedEntities[$incident->getTargetId()])) {
            $referencedEntities[$incident->getTargetId()] = $this->entityTypeManager->getStorage('radioactivity')->load($incident->getTargetId());
          }
          $newEnergy = $referencedEntities[$incident->getTargetId()]->getEnergy() + $incident->getEnergy();
          $referencedEntities[$incident->getTargetId()]->setEnergy($newEnergy);
        }
        else {
          $entity->get($incident->getFieldName())->energy += $incident->getEnergy();
          $hostEntityUpdated = TRUE;
        }
      }

      if ($hostEntityUpdated) {
        if ($entity->getEntityType()->isRevisionable()) {
          $entity->setNewRevision(FALSE);
        }
        // Set flag so we can identify this entity save as one that just updates
        // the radioactivity value.
        $entity->radioactivityUpdate = TRUE;
        $entity->save();
      }

      if ($referencedEntities) {
        foreach ($referencedEntities as $referencedEntity) {
          $referencedEntity->save();
        }
      }
    }
  }

}
