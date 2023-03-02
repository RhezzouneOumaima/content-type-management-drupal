<?php

namespace Drupal\radioactivity\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\radioactivity\RadioactivityProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Radioactivity module queue worker plugins.
 */
abstract class RadioactivityQueueWorkerBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The radioactivity.processor service.
   *
   * @var \Drupal\radioactivity\RadioactivityProcessorInterface
   */
  protected RadioactivityProcessorInterface $radioactivityProcessor;

  /**
   * Constructs a RadioactivityQueueWorkerBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\radioactivity\RadioactivityProcessorInterface $radioactivity_processor
   *   The radioactivity.processor service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, RadioactivityProcessorInterface $radioactivity_processor) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->radioactivityProcessor = $radioactivity_processor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('radioactivity.processor')
    );
  }

}
