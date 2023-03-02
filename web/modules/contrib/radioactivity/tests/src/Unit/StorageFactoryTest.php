<?php

namespace Drupal\Tests\radioactivity\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\radioactivity\DefaultIncidentStorage;
use Drupal\radioactivity\RestIncidentStorage;
use Drupal\radioactivity\StorageFactory;

/**
 * @coversDefaultClass \Drupal\radioactivity\StorageFactory
 * @group radioactivity
 */
class StorageFactoryTest extends UnitTestCase {

  /**
   * Mocked immutable configuration object.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * Mocked class resolver.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  private $classResolver;

  /**
   * Mocked config factory.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\Config\ConfigFactory
   */
  private $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the radioactivity.storage configuration.
    $this->config = $this->createMock(ImmutableConfig::class);

    $this->configFactory = $this->createMock(ConfigFactory::class);
    $this->configFactory->expects($this->any())
      ->method('get')
      ->will($this->returnValue($this->config));

    // Mock the class resolver and the classes it provides.
    $mockRestStorage = $this->createMock(RestIncidentStorage::class);
    $mockDefaultStorage = $this->createMock(DefaultIncidentStorage::class);

    $this->classResolver = $this->createMock(ClassResolverInterface::class);
    $this->classResolver->expects($this->any())
      ->method('getInstanceFromDefinition')
      ->will($this->returnValueMap([
        ['radioactivity.rest_incident_storage', $mockRestStorage],
        ['radioactivity.default_incident_storage', $mockDefaultStorage],
      ]));

  }

  /**
   * @covers ::get
   * @dataProvider providerGet
   */
  public function testGet($storageType, $storageClass) {
    $sut = $this->getMockBuilder(StorageFactory::class)
      ->setMethods()
      ->setConstructorArgs([
        $this->configFactory,
        $this->classResolver,
      ])
      ->getMock();

    $result = $sut->get($storageType);
    $this->assertInstanceOf($storageClass, $result);
  }

  /**
   * Data provider for testGet.
   *
   * @return array
   *   Storage type, storage class.
   */
  public function providerGet() {
    return [
      ['rest_local', RestIncidentStorage::class],
      ['rest_remote', RestIncidentStorage::class],
      ['default', DefaultIncidentStorage::class],
      ['unknown_type', DefaultIncidentStorage::class],
    ];
  }

  /**
   * @covers ::getConfiguredStorage
   * @dataProvider providerGetConfiguredStorage
   */
  public function testGetConfiguredStorage($configType, $storageType) {
    $sut = $this->getMockBuilder('Drupal\radioactivity\StorageFactory')
      ->setMethods(['get'])
      ->setConstructorArgs([
        $this->configFactory,
        $this->classResolver,
      ])
      ->getMock();

    $sut->expects($this->once())
      ->method('get')
      ->with($this->equalTo($storageType));
    $this->setConfig($configType, '');

    $sut->getConfiguredStorage();
  }

  /**
   * Data provider for testGet.
   *
   * @return array
   *   Configured type, storage type.
   */
  public function providerGetConfiguredStorage() {
    return [
      ['rest_local', 'rest_local'],
      ['rest_remote', 'rest_remote'],
      ['default', 'default'],
      [NULL, 'default'],
    ];
  }

  /**
   * Sets mock configuration for StorageFactory.
   *
   * @param mixed $storageType
   *   The configured storage type.
   * @param mixed $endpoint
   *   The configured endpoint.
   */
  private function setConfig($storageType, $endpoint) {
    $this->config->expects($this->any())
      ->method('get')
      ->will($this->returnValueMap([
        ['type', $storageType],
        ['endpoint', $endpoint],
      ]));
  }

}
