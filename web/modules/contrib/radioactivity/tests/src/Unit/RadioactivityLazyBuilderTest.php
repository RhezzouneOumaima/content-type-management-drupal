<?php

namespace Drupal\Tests\radioactivity\Unit;

use Prophecy\PhpUnit\ProphecyTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\radioactivity\RadioactivityInterface;
use Drupal\radioactivity\RadioactivityLazyBuilder;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\radioactivity\RadioactivityLazyBuilder
 * @group radioactivity
 */
class RadioactivityLazyBuilderTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * Mock entity type manager.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $energy = 9.12345;

    $radioactivityEntity = $this->prophesize(RadioactivityInterface::class);
    $radioactivityEntity->getEnergy()
      ->willReturn($energy);
    $radioactivityEntity->getCacheTags()
      ->willReturn(['radioactivity:1']);

    $radioactivityStorage = $this->prophesize(EntityStorageInterface::class);
    $radioactivityStorage->load(Argument::is(0))
      ->willReturn(NULL);
    $radioactivityStorage->load(Argument::is(1))
      ->willReturn($radioactivityEntity->reveal());

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityTypeManager->getStorage('radioactivity')
      ->willReturn($radioactivityStorage->reveal());
  }

  /**
   * @covers ::trustedCallbacks
   */
  public function testTrustedCallbacks() {
    $sut = $this->getMockBuilder(RadioactivityLazyBuilder::class)
      ->setMethods()
      ->setConstructorArgs([
        $this->entityTypeManager->reveal(),
      ])
      ->getMock();

    $this->assertEquals(['buildReferencedValue'], $sut->trustedCallbacks());
  }

  /**
   * @covers ::buildReferencedValue
   * @dataProvider providerBuildReferencedValue
   */
  public function testBuildReferencedValue($entityId, $decimals, $expected) {
    $sut = $this->getMockBuilder(RadioactivityLazyBuilder::class)
      ->setMethods()
      ->setConstructorArgs([
        $this->entityTypeManager->reveal(),
      ])
      ->getMock();

    $this->assertEquals($expected, $sut->buildReferencedValue($entityId, $decimals));
  }

  /**
   * Data provider for testBuildReferencedValue.
   *
   * @return array
   *   Entity ID, Decimals, Return value of::buildReferencedValue.
   */
  public function providerBuildReferencedValue() {
    return [
      [0, 0, []],
      [1, 0, [
        '#markup' => '9',
        '#cache' => ['tags' => ['radioactivity:1']],
      ]],
      [1, 1, [
        '#markup' => '9.1',
        '#cache' => ['tags' => ['radioactivity:1']],
      ]],
      [1, 2, [
        '#markup' => '9.12',
        '#cache' => ['tags' => ['radioactivity:1']],
      ]],
      [1, NULL, [
        '#markup' => '9.12345',
        '#cache' => ['tags' => ['radioactivity:1']],
      ]],
    ];
  }

}
