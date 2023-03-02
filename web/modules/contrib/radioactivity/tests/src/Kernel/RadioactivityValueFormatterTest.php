<?php

namespace Drupal\Tests\radioactivity\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\Tests\radioactivity\Traits\RadioactivityFunctionTestTrait;

/**
 * @coversDefaultClass \Drupal\radioactivity\Plugin\Field\FieldFormatter\RadioactivityValue
 * @group radioactivity
 */
class RadioactivityValueFormatterTest extends FieldKernelTestBase {

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->createEnergyField('field_radioactivity', 'count');

    $this->entity = EntityTest::create([
      'field_radioactivity' => [
        'energy' => 5.55555,
        'timestamp' => 0,
      ],
    ]);
    $this->entity->save();
  }

  /**
   * Tests the (rounded) value formatter.
   *
   * @param int $decimals
   *   Number of decimals to display.
   * @param string $expected
   *   Expected output.
   *
   * @dataProvider formatterProvider
   *
   * @throws \Exception
   */
  public function testFormatter($decimals, $expected) {
    $viewBuilder = \Drupal::entityTypeManager()->getViewBuilder($this->entityType);
    $field = $this->entity->get('field_radioactivity');
    $build = $viewBuilder->viewField($field, [
      'label' => 'hidden',
      'type' => 'radioactivity_value',
      'settings' => ['decimals' => $decimals],
    ]);
    $output = $this->render($build);

    $this->assertTrue(strpos($output, $expected) !== FALSE, 'Output contains a rounded value');
  }

  /**
   * Data provider for energy field formatter.
   *
   * @return array
   *   Data: number of decimals, formatter output.
   */
  public function formatterProvider() {
    return [
      [0, '<div>6</div>'],
      [2, '<div>5.56</div>'],
    ];
  }

}
