<?php

namespace Drupal\Tests\radioactivity\Unit;

use Drupal\radioactivity\RestProcessor;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \Drupal\radioactivity\RestProcessor
 * @group radioactivity
 */
class RestProcessorTest extends UnitTestCase {

  /**
   * The Rest Processor under test.
   *
   * @var \Drupal\radioactivity\RestProcessor
   */
  private $sut;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $workDirectory = vfsStream::setup('radioactivity');

    $this->sut = new RestProcessor([
      'payload_file' => $workDirectory->url() . '/radioactivity-payload.json',
    ]);
  }

  /**
   * @covers ::processData
   */
  public function testProcessData() {
    $data = '[{"fn":"field_ra","et":"node","id":"66","ti":0,"e":10,"h":"12345678"}]';
    $response = $this->sut->processData($data);
    $this->assertEquals('{"status":"ok","message":"Inserted."}', $response);
  }

  /**
   * @covers ::processData
   */
  public function testProcessFaultyData() {
    $data = '[{"fn":"field_ra","et":"node","id":"66","ti":0,"e":10}]';
    $response = $this->sut->processData($data);
    $this->assertEquals('{"status":"error","message":"Invalid json."}', $response);
  }

  /**
   * @covers ::getData
   */
  public function testGetData() {
    $data = '[{"fn":"field_ra","et":"node","id":"66","ti":0,"e":10,"h":"12345678"}]';
    $this->sut->processData($data);

    $response = $this->sut->getData();
    $this->assertEquals('[[{"fn":"field_ra","et":"node","id":"66","ti":0,"e":10,"h":"12345678"}]]', $response);
  }

  /**
   * @covers ::getData
   */
  public function testGetMultiData() {
    $data = '[{"fn":"field_ra","et":"node","id":"66","ti":0,"e":10,"h":"12345678"}]';
    $this->sut->processData($data);
    $data = '[{"fn":"field_ra","et":"node","id":"66","ti":0,"e":10,"h":"87654321"}]';
    $this->sut->processData($data);

    $response = $this->sut->getData();
    $this->assertEquals('[[{"fn":"field_ra","et":"node","id":"66","ti":0,"e":10,"h":"12345678"}],' . PHP_EOL .
      '[{"fn":"field_ra","et":"node","id":"66","ti":0,"e":10,"h":"87654321"}]]', $response);
  }

  /**
   * @covers ::getData
   */
  public function testGetEmptyData() {
    $response = $this->sut->getData();
    $this->assertEquals('[]', $response);
  }

  /**
   * @covers ::clearData
   */
  public function testClearData() {
    $data = '[{"fn":"field_ra","et":"node","id":"66","ti":0,"e":10,"h":"12345678"}]';
    $this->sut->processData($data);

    $response = $this->sut->getData();
    $this->assertEquals('[[{"fn":"field_ra","et":"node","id":"66","ti":0,"e":10,"h":"12345678"}]]', $response);

    $response = $this->sut->clearData();
    $this->assertEquals('{"status":"ok","message":"Cleared."}', $response);

    $response = $this->sut->getData();
    $this->assertEquals('[]', $response);
  }

  /**
   * @covers ::error
   */
  public function testErrorResponse() {
    $response = $this->sut->error();
    $this->assertEquals('{"status":"error","message":"Nothing to do."}', $response);
  }

}
