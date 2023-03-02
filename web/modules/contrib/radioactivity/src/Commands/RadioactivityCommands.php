<?php

namespace Drupal\radioactivity\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\radioactivity\radioactivityReferenceUpdaterInterface;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for Radioactivity.
 */
class RadioactivityCommands extends DrushCommands {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The radioactivity reference updater.
   *
   * @var \Drupal\radioactivity\RadioactivityReferenceUpdaterInterface
   */
  protected $radioactivityReferenceUpdater;

  /**
   * RadioactivityCommands constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RadioactivityReferenceUpdaterInterface $radioactivityReferenceUpdater) {
    parent::__construct();

    $this->entityTypeManager = $entityTypeManager;
    $this->radioactivityReferenceUpdater = $radioactivityReferenceUpdater;
  }

  /**
   * Update radioactivity references.
   *
   * @usage radioactivity:fix-references
   *   Fixes empty radioactivity reference fields by adding a reference.
   *
   * @command radioactivity:fix-references
   */
  public function radioactivityFixReferences() {

    $entitiesWithoutTarget = $this->radioactivityReferenceUpdater->getReferencesWithoutTarget();
    if (empty($entitiesWithoutTarget)) {
      $this->logger->warning(dt('No radioactivity reference fields found that need to be fixed.'));
      return;
    }

    // @todo Perform this in batches.
    foreach ($entitiesWithoutTarget as $item) {
      $entityStorage = $this->entityTypeManager->getStorage($item['entity_type']);
      /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
      $entity = $entityStorage->load($item['id']);
      $this->radioactivityReferenceUpdater->updateReferenceFields($entity);
    }

    $this->logger->success(dt('@count entities with radioactivity reference field fixed.', ['@count' => count($entitiesWithoutTarget)]));
  }

}
