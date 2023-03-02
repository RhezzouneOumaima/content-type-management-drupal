<?php

namespace Drupal\Tests\radioactivity\Unit;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;
use Drupal\radioactivity\IncidentInterface;
use Drupal\radioactivity\RestIncidentStorage;

/**
 * @coversDefaultClass \Drupal\radioactivity\RestIncidentStorage
 * @group radioactivity
 */
class RestIncidentStorageTest extends UnitTestCase {

  /**
   * The RestIncidentStorage under test.
   *
   * @var \Drupal\radioactivity\RestIncidentStorage
   */
  private $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->sut = $this->getMockBuilder(RestIncidentStorage::class)
      ->disableOriginalConstructor()
      ->onlyMethods([
        'getIncidentsFromStorage',
        'clearIncidentStorage',
      ])
      ->getMock();

    // Initiate the Settings singleton used by this test.
    new Settings([
      'hash_salt' => 'liesjeleerdelotjelopen',
    ]);
  }

  /**
   * @covers ::addIncident
   */
  public function testAddIncident() {
    $incident = $this->createMock(IncidentInterface::class);

    $this->expectException("Exception");
    $this->expectExceptionMessage("The Radioactivity rest endpoint expects incidents to be added somewhere else.");

    $this->sut->addIncident($incident);
  }

  /**
   * @covers ::getIncidents
   */
  public function testGetSingleIncident() {
    $incidentData = Json::decode('[[{"fn":"field_name","et":"entity_type","id":"99","ti":"0","e":"10","h":"4bd2afb1d12a72f3a2fdb01b8fdaf128b8c09efa"}]]');

    $this->sut->expects($this->once())
      ->method('getIncidentsFromStorage')
      ->will($this->returnValue($incidentData));

    $result = $this->sut->getIncidents();
    $this->assertCount(1, $result);
    $this->assertInstanceOf(IncidentInterface::class, $result[0]);
  }

  /**
   * @covers ::getIncidents
   */
  public function testGetInvalidIncident() {
    $incidentData = Json::decode('[[{"fn":"field_name","et":"entity_type","id":"99","ti":0,"e":10,"h":"invalid-hash"}]]');

    $this->sut->expects($this->once())
      ->method('getIncidentsFromStorage')
      ->will($this->returnValue($incidentData));

    $result = $this->sut->getIncidents();
    $this->assertCount(0, $result);
  }

  /**
   * @covers ::getIncidents
   */
  public function testGetMultipleIncidents() {
    $this->sut->expects($this->once())
      ->method('getIncidentsFromStorage')
      ->will($this->returnValue($this->getMultipleIncidentData()));

    $result = $this->sut->getIncidents();
    $this->assertCount(4, $result);
    $this->assertInstanceOf(IncidentInterface::class, $result[0]);
  }

  /**
   * @covers ::getIncidentsByType
   */
  public function testGetIncidentsByType() {
    $this->sut->expects($this->any())
      ->method('getIncidentsFromStorage')
      ->will($this->returnValue($this->getMultipleIncidentData()));

    $result = $this->sut->getIncidentsByType();
    $this->assertEquals(['entity_type', 'node'], array_keys($result));
    $this->assertEquals([99, 88], array_keys($result['entity_type']));
    $this->assertInstanceOf(IncidentInterface::class, $result['entity_type'][99][0]);
    $this->assertEquals([123], array_keys($result['node']));
    $this->assertInstanceOf(IncidentInterface::class, $result['node'][123][0]);

    $result = $this->sut->getIncidentsByType('entity_type');
    $this->assertEquals(['entity_type'], array_keys($result));
    $this->assertEquals([99, 88], array_keys($result['entity_type']));
    $this->assertInstanceOf(IncidentInterface::class, $result['entity_type'][99][0]);

    $result = $this->sut->getIncidentsByType('node');
    $this->assertEquals([123], array_keys($result['node']));
    $this->assertInstanceOf(IncidentInterface::class, $result['node'][123][0]);

    $result = $this->sut->getIncidentsByType('unknown_entity');
    $this->assertArrayNotHasKey('unknown_entity', $result);
  }

  /**
   * @covers ::clearIncidents
   */
  public function testClearIncidents() {
    $this->sut->expects($this->once())
      ->method('clearIncidentStorage');

    $this->sut->clearIncidents();
  }

  /**
   * Returns incident data as returned by RestProcessor::getData.
   *
   * @return array
   *   Decoded rest incident storage data.
   */
  private function getMultipleIncidentData() {
    $jsonData =
      '[[{"fn":"field_name","et":"entity_type","id":"99","ti":"0","e":"10","h":"4bd2afb1d12a72f3a2fdb01b8fdaf128b8c09efa"}],' . PHP_EOL .
      '[{"fn":"field_name","et":"entity_type","id":"88","ti":0,"e":10,"h":"22c8151d3da778780c003131c64e6bc3dfd81959"}],' . PHP_EOL .
      '[{"fn":"field_name","et":"entity_type","id":"88","ti":0,"e":10,"h":"22c8151d3da778780c003131c64e6bc3dfd81959"}],' . PHP_EOL .
      '[{"fn":"field_other","et":"node","id":"123","ti":0,"e":10,"h":"0f044221c4c7a1db2b42549dfd412e5c49c44ec4"}]]';
    return Json::decode($jsonData);
  }

}
