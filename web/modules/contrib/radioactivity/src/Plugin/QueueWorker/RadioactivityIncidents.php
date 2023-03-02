<?php

namespace Drupal\radioactivity\Plugin\QueueWorker;

/**
 * Processes radioactivity emits.
 *
 * @QueueWorker(
 *   id = "radioactivity_incidents",
 *   title = @Translation("Process radioactivity incidents"),
 *   cron = {"time" = 10}
 * )
 */
class RadioactivityIncidents extends RadioactivityQueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $this->radioactivityProcessor->queueProcessIncidents($data['entity_type'], $data['incidents']);
  }

}
