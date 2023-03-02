<?php

namespace Drupal\radioactivity;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ClassResolverInterface;

/**
 * Storage factory service.
 */
class StorageFactory {

  /**
   * The radioactivity storage configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected ClassResolverInterface $classResolver;

  /**
   * StorageFactory constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $classResolver
   *   The class resolver.
   */
  public function __construct(ConfigFactoryInterface $configFactory, ClassResolverInterface $classResolver) {
    $this->config = $configFactory->get('radioactivity.storage');
    $this->classResolver = $classResolver;
  }

  /**
   * Getter for classes which implement IncidentStorageInterface.
   *
   * @param string $type
   *   The type of storage to get.
   *
   * @return \Drupal\radioactivity\IncidentStorageInterface
   *   Instance of the requested storage.
   */
  public function get(string $type): IncidentStorageInterface {

    switch ($type) {
      case 'rest_local':
        $instance = $this->classResolver->getInstanceFromDefinition('radioactivity.rest_incident_storage');
        $instance->setEndpoint(NULL);
        break;

      case 'rest_remote':
        $instance = $this->classResolver->getInstanceFromDefinition('radioactivity.rest_incident_storage');
        $instance->setEndpoint($this->config->get('endpoint'));
        break;

      case 'default':
      default:
        $instance = $this->classResolver->getInstanceFromDefinition('radioactivity.default_incident_storage');
    }

    /** @var \Drupal\radioactivity\IncidentStorageInterface $instance */
    return $instance;
  }

  /**
   * Get the configured incident storage.
   *
   * @return \Drupal\radioactivity\IncidentStorageInterface
   *   The configured storage instance.
   */
  public function getConfiguredStorage(): IncidentStorageInterface {
    $type = $this->config->get('type') ?: 'default';
    return $this->get($type);
  }

}
