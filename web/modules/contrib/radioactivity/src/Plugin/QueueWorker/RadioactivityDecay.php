<?php

namespace Drupal\radioactivity\Plugin\QueueWorker;

/**
 * Processes radioactivity decay.
 *
 * @QueueWorker(
 *   id = "radioactivity_decay",
 *   title = @Translation("Process radioactivity decay"),
 *   cron = {"time" = 10}
 * )
 */
class RadioactivityDecay extends RadioactivityQueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $this->radioactivityProcessor->queueProcessDecay($data['field_config'], $data['entity_ids']);
  }

}
