<?php

namespace Drupal\Tests\radioactivity\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\radioactivity\Incident;
use Drupal\radioactivity\IncidentInterface;

/**
 * @coversDefaultClass \Drupal\radioactivity\DefaultIncidentStorage
 * @group radioactivity
 */
class DefaultIncidentStorageTest extends KernelTestBase {


  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'radioactivity',
  ];

  /**
   * The system under test.
   *
   * @var \Drupal\radioactivity\DefaultIncidentStorage
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('radioactivity', ['radioactivity_incident']);

    $this->sut = \Drupal::service('radioactivity.default_incident_storage');
  }

  /**
   * @covers ::addIncident
   * @covers ::getIncidents
   * @covers ::clearIncidents
   */
  public function testAddIncident() {
    $incident = new Incident('field_name', 'entity_type', '3', NULL, 5.5);

    $result0 = $this->sut->getIncidents();
    $this->sut->addIncident($incident);
    $result1 = $this->sut->getIncidents();
    $this->sut->addIncident($incident);
    $result2 = $this->sut->getIncidents();

    $this->assertEquals(0, count($result0));
    $this->assertEquals(1, count($result1));
    $this->assertEquals(2, count($result2));

    $this->assertTrue(reset($result1) instanceof IncidentInterface);

    $this->sut->clearIncidents();
    $resultClear = $this->sut->getIncidents();

    $this->assertEquals(0, count($resultClear));
  }


  /**
   * @covers ::getIncidentsByType
   */
  public function testGetIncidentsByType() {
    $incident1 = new Incident('field1', 'type1', '123', NULL, 1.1);
    $this->sut->addIncident($incident1);
    $incident2 = new Incident('field1', 'type1', '234', NULL, 2.2);
    $this->sut->addIncident($incident2);
    $incident3 = new Incident('field1', 'type2', '345', NULL, 3.3);
    $this->sut->addIncident($incident3);

    $result = $this->sut->getIncidentsByType('type1');

    $this->assertEquals(['type1'], array_keys($result));
    $this->assertEquals(['123', '234'], array_keys($result['type1']));
    $this->assertEquals($result['type1']['234'][0], $incident2);

    $result = $this->sut->getIncidentsByType();

    $this->assertEquals(['type1', 'type2'], array_keys($result));
    $this->assertEquals(['345'], array_keys($result['type2']));
    $this->assertEquals($result['type2']['345'][0], $incident3);

    $result = $this->sut->getIncidentsByType('unknown_entity');
    $this->assertArrayNotHasKey('unknown_entity', $result);
  }

  /**
   * @covers ::injectSettings
   */
  public function testInjectSettings() {
    $page = [];
    $this->sut->injectSettings($page);
    $this->assertTrue(isset($page['#attached']['drupalSettings']['radioactivity']));
  }

}
