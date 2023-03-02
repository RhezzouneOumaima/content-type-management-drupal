<?php

namespace Drupal\Tests\radioactivity\Unit;

use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;
use Drupal\radioactivity\Incident;

/**
 * @coversDefaultClass \Drupal\radioactivity\Incident
 * @group radioactivity
 */
class IncidentTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Initiate the Settings singleton used by this test.
    new Settings([
      'hash_salt' => 'liesjeleerdelotjelopen',
    ]);
  }

  /**
   * @covers ::getFieldName
   * @covers ::getEntityTypeId
   * @covers ::getEntityId
   * @covers ::getEnergy
   */
  public function testGetters() {
    $incident = new Incident('field_name', 'entity_type', '99', '0', '10', '1234567890');

    $this->assertEquals('field_name', $incident->getFieldName());
    $this->assertEquals('entity_type', $incident->getEntityTypeId());
    $this->assertEquals('99', $incident->getEntityId());
    $this->assertEquals('0', $incident->getTargetId());
    $this->assertEquals('10', $incident->getEnergy());
  }

  /**
   * @covers ::createFromPostData
   */
  public function testCreateFromPostData() {
    $incident = Incident::createFromPostData([
      'fn' => 'field_name',
      'et' => 'entity_type',
      'id' => '99',
      'ti' => '0',
      'e' => '10',
      'h' => '1234567890',
    ]);

    $this->assertEquals('field_name', $incident->getFieldName());
    $this->assertEquals('entity_type', $incident->getEntityTypeId());
    $this->assertEquals('99', $incident->getEntityId());
    $this->assertEquals('0', $incident->getTargetId());
    $this->assertEquals('10', $incident->getEnergy());
  }

  /**
   * @covers ::toJson
   */
  public function testJson() {

    $incident = new Incident('field_name', 'entity_type', '99', '0', '10', '1234567890');
    $this->assertEquals('{"fn":"field_name","et":"entity_type","id":"99","ti":"0","e":"10","h":"4bd2afb1d12a72f3a2fdb01b8fdaf128b8c09efa"}', $incident->toJson());
  }

  /**
   * @covers ::isValid
   */
  public function testValidHash() {
    $incident = new Incident('field_name', 'entity_type', '99', '0', '10', '1234567890');
    $this->assertFalse($incident->isValid());

    $incident = new Incident('field_name', 'entity_type', '99', '0', '10', '4bd2afb1d12a72f3a2fdb01b8fdaf128b8c09efa');
    $this->assertTrue($incident->isValid());
  }

}
