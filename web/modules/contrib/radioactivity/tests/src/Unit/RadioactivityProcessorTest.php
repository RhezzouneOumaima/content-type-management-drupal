<?php

namespace Drupal\Tests\radioactivity\Unit;

use Prophecy\PhpUnit\ProphecyTrait;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\State\StateInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\radioactivity\IncidentInterface;
use Drupal\radioactivity\IncidentStorageInterface;
use Drupal\radioactivity\RadioactivityProcessor;
use Drupal\radioactivity\RadioactivityProcessorInterface;
use Drupal\radioactivity\StorageFactory;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @coversDefaultClass \Drupal\radioactivity\RadioactivityProcessor
 * @group radioactivity
 */
class RadioactivityProcessorTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The radioactivity processor under test.
   *
   * @var \Drupal\radioactivity\RadioactivityProcessorInterface
   */
  protected $sut;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Mock field storage configuration.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fieldStorageConfig;

  /**
   * Mock state system.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Mock logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Mock Radioactivity logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $loggerChannel;

  /**
   * The radioactivity storage factory.
   *
   * @var \Drupal\radioactivity\StorageFactory
   */
  protected $storage;

  /**
   * The radioactivity incident storage.
   *
   * @var \Drupal\radioactivity\IncidentStorageInterface
   */
  protected $incidentStorage;

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $eventDispatcher;

  /**
   * Dummy request timestamp.
   *
   * @var int
   */
  protected $requestTime = 1000;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->prophesize(EntityTypeManager::class);
    $this->fieldStorageConfig = $this->prophesize(EntityStorageInterface::class);
    $this->entityTypeManager->getStorage('field_storage_config')
      ->willReturn($this->fieldStorageConfig->reveal());

    $this->state = $this->prophesize(StateInterface::class);
    $loggerFactory = $this->prophesize(LoggerChannelFactoryInterface::class);
    $this->loggerChannel = $this->prophesize(LoggerChannelInterface::class);
    $loggerFactory->get(RadioactivityProcessorInterface::LOGGER_CHANNEL)
      ->willReturn($this->loggerChannel->reveal());

    $this->storage = $this->prophesize(StorageFactory::class);

    $this->incidentStorage = $this->prophesize(IncidentStorageInterface::class);
    $this->storage->getConfiguredStorage()->willReturn($this->incidentStorage->reveal());

    $time = $this->prophesize(TimeInterface::class);
    $time->getRequestTime()->willReturn($this->requestTime);

    $this->queueFactory = $this->prophesize(QueueFactory::class);
    $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

    $this->sut = $this->getMockBuilder(RadioactivityProcessor::class)
      ->onlyMethods([
        'getRadioactivityFieldsConfig',
      ])
      ->setConstructorArgs([
        $this->entityTypeManager->reveal(),
        $this->state->reveal(),
        $loggerFactory->reveal(),
        $this->storage->reveal(),
        $time->reveal(),
        $this->queueFactory->reveal(),
        $this->eventDispatcher->reveal(),
      ])
      ->getMock();
  }

  /**
   * @covers ::processDecayByFieldType
   */
  public function testProcessDecayNoFields() {

    $this->sut->expects($this->any())
      ->method('getRadioactivityFieldsConfig')
      ->will($this->returnValue([]));

    $result = $this->sut->processDecay();
    $this->assertEquals(0, $result);
  }

  /**
   * @covers ::processDecay
   */
  public function testProcessDecayNoData() {

    $profile = 'count';
    $hasData = FALSE;
    $resultCount = 0;

    $configData = $this->prophesize(FieldStorageConfig::class);
    $configData->getSetting('profile')->willReturn($profile);
    $configData->hasData()->willReturn($hasData);

    $data = [$configData->reveal()];
    $this->sut->expects($this->any())
      ->method('getRadioactivityFieldsConfig')
      ->will($this->returnValueMap([
        ['radioactivity', $data],
        ['radioactivity_reference', $data],
      ]));

    $this->state->set(RadioactivityProcessorInterface::LAST_PROCESSED_STATE_KEY, Argument::any())
      ->shouldBeCalled();
    $this->loggerChannel->notice('Processed @count radioactivity decays.', ['@count' => $resultCount])
      ->shouldBeCalled();

    $result = $this->sut->processDecay();
    $this->assertEquals($resultCount, $result);
  }

  /**
   * @covers ::processDecay
   */
  public function testProcessDecayCountProfile() {

    $profile = 'count';
    $hasData = TRUE;
    $resultCount = 0;

    $configData1 = $this->prophesize(FieldStorageConfig::class);
    $configData1->getSetting('profile')->willReturn($profile);
    $configData1->hasData()->willReturn($hasData);

    $data = [$configData1->reveal()];
    $this->sut->expects($this->any())
      ->method('getRadioactivityFieldsConfig')
      ->will($this->returnValueMap([
        ['radioactivity', $data],
        ['radioactivity_reference', $data],
      ]));

    $this->state->set(RadioactivityProcessorInterface::LAST_PROCESSED_STATE_KEY, Argument::any())
      ->shouldBeCalled();
    $this->loggerChannel->notice('Processed @count radioactivity decays.', ['@count' => $resultCount])
      ->shouldBeCalled();

    $result = $this->sut->processDecay();
    $this->assertEquals($resultCount, $result);
  }

  /**
   * @covers ::queueProcessDecay
   * @dataProvider providerQueueProcessDecay
   */
  public function testQueueProcessDecay($fieldType, $profile, $halfLife, $cutoff, $initialEnergy, $elapsedTime, $isPublished, $langcode, $resultEnergy) {

    $fieldConfig = $this->prophesize(FieldStorageConfigInterface::class);
    $fieldConfig->getTargetEntityTypeId()->willReturn('entity_test');
    $fieldConfig->get('field_name')->willReturn('ra_field');
    $fieldConfig->getSetting('profile')->willReturn($profile);
    $fieldConfig->getSetting('halflife')->willReturn($halfLife);
    $fieldConfig->getSetting('cutoff')->willReturn($cutoff);

    $fieldItemList = $this->createMock(FieldItemListInterface::class);
    $fieldItemList->expects($this->any())
      ->method('__get')
      ->willReturn($this->returnValueMap([
        ['energy', $initialEnergy],
        ['timestamp', $this->requestTime - $elapsedTime],
      ]));
    $fieldItemList->expects($isPublished ? $this->once() : $this->never())
      ->method('setValue')
      ->with([
        'energy' => $resultEnergy,
        'timestamp' => $this->requestTime,
      ]);

    $fieldDefinition = $this->prophesize(FieldDefinitionInterface::class);
    $fieldDefinition->getType()->willReturn($fieldType);
    $fieldItemList->expects($this->any())
      ->method('getFieldDefinition')
      ->willReturn($fieldDefinition);

    $language = $this->prophesize(LanguageInterface::class);
    $language->getId()->willReturn($langcode);

    /** @var \Drupal\Core\Entity\EntityTypeInterface $entityType */
    $entityType = $this->prophesize(EntityTypeInterface::class);
    $entityType->isRevisionable()->willReturn(FALSE);
    /** @var \Drupal\Core\Entity\ContentEntityInterface|\Drupal\Core\Entity\EntityPublishedInterface $entity */
    $entity = $this->prophesize(PublishedContentEntityInterface::class);
    $entity->getEntityType()->willReturn($entityType);
    $entity->isPublished()->willReturn($isPublished);
    $entity->get('ra_field')->willReturn($fieldItemList);
    $entity->save()->shouldBeCalledTimes($isPublished ? 1 : 0);
    $entity->getTranslationLanguages()->willReturn([$language]);
    $entity->getTranslation($langcode)->willReturn($entity);

    $entityStorage = $this->prophesize(EntityStorageInterface::class);
    $entityStorage->loadMultiple([123])
      ->willReturn([$entity->reveal()]);

    $this->entityTypeManager->getStorage('entity_test')
      ->willReturn($entityStorage->reveal());

    $this->sut->queueProcessDecay($fieldConfig->reveal(), [123]);
  }

  /**
   * Data provider for testQueueProcessDecay.
   *
   * @return array
   *   - field type
   *   - profile,
   *   - half life,
   *   - cutoff,
   *   - initial energy,
   *   - timestamp,
   *   - is published,
   *   - langcode,
   *   - resulting energy
   */
  public function providerQueueProcessDecay() {
    return [
      ['radioactivity', 'count', 10, 10, 100, 10, TRUE, 'en', 100],
      ['radioactivity', 'linear', 10, 10, 100, 0, TRUE, 'en', 100],
      ['radioactivity', 'linear', 10, 10, 100, 10, TRUE, 'en', 90],
      ['radioactivity', 'linear', 10, 10, 100, 90, TRUE, 'en', 0],
      ['radioactivity', 'decay', 10, 10, 100, 0, TRUE, 'en', 100],
      ['radioactivity', 'decay', 10, 10, 100, 10, TRUE, 'en', 50],
      ['radioactivity', 'decay', 10, 10, 100, 20, TRUE, 'en', 25],
      ['radioactivity', 'decay', 10, 30, 100, 20, TRUE, 'en', 0],
      ['radioactivity', 'decay', 5, 10, 100, 10, TRUE, 'en', 25],
      ['radioactivity', 'count', 10, 10, 100, 10, FALSE, 'en', 0],
      ['radioactivity', 'linear', 10, 10, 100, 10, FALSE, 'en', 100],
      ['radioactivity', 'decay', 10, 10, 100, 10, FALSE, 'en', 100],
    ];
  }

  /**
   * @covers ::processIncidents
   */
  public function testProcessIncidents() {

    $incidentsByType['entity_type_a'] = [
      'incidentA1',
      'incidentA2',
      'incidentA3',
    ];
    $incidentsByType['entity_type_b'] = [
      'incidentB1',
      'incidentB2',
      'incidentB3',
      'incidentB4',
      'incidentB5',
      'incidentB6',
      'incidentB7',
      'incidentB8',
      'incidentB9',
      'incidentB10',
      'incidentB11',
      'incidentB12',
    ];

    $this->incidentStorage->getIncidentsByType()->willReturn($incidentsByType);
    $this->incidentStorage->clearIncidents()->shouldBeCalled();

    $queue = $this->prophesize(QueueInterface::class);
    $this->queueFactory->get(RadioactivityProcessorInterface::QUEUE_WORKER_INCIDENTS)->willReturn($queue->reveal());
    $queue->createItem([
      'entity_type' => 'entity_type_a',
      'incidents' => [
        0 => 'incidentA1',
        1 => 'incidentA2',
        2 => 'incidentA3',
      ],
    ])->shouldBeCalledTimes(1);
    $queue->createItem([
      'entity_type' => 'entity_type_b',
      'incidents' => [
        0 => 'incidentB1',
        1 => 'incidentB2',
        2 => 'incidentB3',
        3 => 'incidentB4',
        4 => 'incidentB5',
        5 => 'incidentB6',
        6 => 'incidentB7',
        7 => 'incidentB8',
        8 => 'incidentB9',
        9 => 'incidentB10',
      ],
    ])->shouldBeCalledTimes(1);
    $queue->createItem([
      'entity_type' => 'entity_type_b',
      'incidents' => [
        10 => 'incidentB11',
        11 => 'incidentB12',
      ],
    ])->shouldBeCalledTimes(1);

    $this->loggerChannel->notice('Processed @count radioactivity incidents.', ['@count' => 15])
      ->shouldBeCalled();

    $result = $this->sut->processIncidents();
    $this->assertEquals(15, $result);
  }

  /**
   * @covers ::queueProcessIncidents
   * @dataProvider providerQueueProcessIncidents
   */
  public function testQueueProcessIncidents($isRevisonable, $initialEnergy, $emittedEnergy, $resultEnergy) {

    $energyField = (object) [
      'energy' => $initialEnergy,
      'timestamp' => $this->requestTime,
    ];

    /** @var \Drupal\Core\Entity\EntityTypeInterface $entityType */
    $entityType = $this->prophesize(EntityTypeInterface::class);
    $entityType->isRevisionable()->willReturn($isRevisonable);

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->prophesize(ContentEntityInterface::class);
    $entity->getEntityType()->willReturn($entityType);
    $entity->id()->willReturn(123);
    $entity->get('ra_field')->willReturn($energyField);
    $entity->setNewRevision(FALSE)->shouldBeCalledTimes($isRevisonable ? 1 : 0);
    $entity->save()->shouldBeCalled();

    // Prophesize entityTypeManager->getStorage->loadMultiple.
    $entityStorage = $this->prophesize(EntityStorageInterface::class);
    $entityStorage->loadMultiple([123])
      ->willReturn(['123' => $entity->reveal()]);
    $this->entityTypeManager->getStorage('entity_test')
      ->willReturn($entityStorage->reveal());

    $incident = $this->prophesize(IncidentInterface::class);
    $incident->getFieldName()->willReturn('ra_field');
    $incident->getEnergy()->willReturn($emittedEnergy);
    $incident->getTargetId()->willReturn(0);
    $this->sut->queueProcessIncidents('entity_test', ['123' => [$incident->reveal()]]);

    // @todo Find a way to check the resulting energy value.
  }

  /**
   * @covers ::processIncidents
   *
   * @return array
   *   isRevisionable, initialEnergy, emittedEnergy, resultEnergy.
   */
  public function providerQueueProcessIncidents() {
    return [
      [TRUE, 0, 10, 10],
      [FALSE, 0, 10, 10],
    ];
  }

}

/**
 * Interface that combines multiple interfaces for testing purpose.
 */
interface PublishedContentEntityInterface extends ContentEntityInterface, EntityPublishedInterface {}
