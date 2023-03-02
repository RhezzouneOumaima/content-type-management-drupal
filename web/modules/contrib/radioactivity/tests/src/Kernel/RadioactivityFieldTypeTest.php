<?php

namespace Drupal\Tests\radioactivity\Kernel;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\Tests\radioactivity\Traits\RadioactivityFunctionTestTrait;

/**
 * @coversDefaultClass \Drupal\radioactivity\Plugin\Field\FieldType\RadioactivityField
 * @group radioactivity
 */
class RadioactivityFieldTypeTest extends FieldKernelTestBase {

  use RadioactivityFunctionTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'radioactivity',
    'entity_test',
  ];

  /**
   * The entity view display object.
   *
   * @var \Drupal\Core\Entity\Entity\EntityViewDisplay
   */
  protected $entityViewDisplay;

  /**
   * The entity that contains the energy field.
   *
   * @var \Drupal\Core\Entity\FieldableEntityInterface
   */
  protected $entity;

  /**
   * Custom request time.
   *
   * @var int
   */
  protected $requestTime;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock Time::getRequestTime.
    $dateTime = $this->createMock(TimeInterface::class);
    $dateTime
      ->expects($this->any())
      ->method('getRequestTime')
      ->willReturnCallback(function () {
        return $this->requestTime;
      });
    $this->container->set('datetime.time', $dateTime);
  }

  /**
   * @covers ::preSave
   */
  public function testSave() {
    $defaultEnergy = 99;
    $this->createEnergyField('field_radioactivity', 'count', $defaultEnergy);

    // Creating an entity.
    $this->setRequestTime(1000);
    $this->entity = EntityTest::create();
    $this->entity->save();
    $this->assertEquals($defaultEnergy, $this->entity->get('field_radioactivity')->energy);
    $this->assertEquals(1000, $this->entity->get('field_radioactivity')->timestamp);

    // Updating an entity without changing the energy.
    $this->setRequestTime(1010);
    $this->entity->save();
    $this->assertEquals(1000, $this->entity->get('field_radioactivity')->timestamp);

    // Updating an entity with changing the energy.
    $this->setRequestTime(1020);
    $this->entity->get('field_radioactivity')->energy = 88;
    $this->entity->save();
    $this->assertEquals(88, $this->entity->get('field_radioactivity')->energy);
    $this->assertEquals(1020, $this->entity->get('field_radioactivity')->timestamp);

    // @todo Test Unpublishing + Publishing an entity.
    // Use a Node instead of TestEntity.
  }

  /**
   * Set the custom request time.
   *
   * @param int $time
   *   The request time.
   */
  private function setRequestTime(int $time) {
    $this->requestTime = $time;
  }

}
