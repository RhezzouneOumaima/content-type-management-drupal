<?php

namespace Drupal\Tests\radioactivity\Unit;

use Prophecy\PhpUnit\ProphecyTrait;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\node\NodeInterface;
use Drupal\radioactivity\RadioactivityReferenceUpdater;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\radioactivity\RadioactivityReferenceUpdater
 * @group radioactivity
 */
class RadioactivityReferenceUpdaterTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * @covers ::hasMissingReferences
   * @dataProvider providerHasMissingReferences
   */
  public function testHasMissingReferences($referencesWithoutTarget, $hasMissingReferences) {
    $sut = $this->getMockBuilder(RadioactivityReferenceUpdater::class)
      ->onlyMethods([
        'getReferencesWithoutTarget',
      ])
      ->disableOriginalConstructor()
      ->getMock();

    $sut->expects($this->any())
      ->method('getReferencesWithoutTarget')
      ->will($this->returnValue($referencesWithoutTarget));

    $this->assertEquals($hasMissingReferences, $sut->hasMissingReferences());
  }

  /**
   * Data provider for testHasMissingReferences.
   *
   * @return array
   *   - ::getReferencesWithoutTarget result value
   *   - ::hasMissingReferences result
   */
  public function providerHasMissingReferences(): array {
    return [
      // No entities with entity reference field without target.
      [
        [],
        FALSE,
      ],
      // One entity with radioactivity reference field without target.
      [
        [
          'node:1' => [
            'entity_type' => 'node',
            'id' => '1',
          ],
        ],
        TRUE,
      ],
      // Two entities with radioactivity reference field without target.
      [
        [
          'node:1' => [
            'entity_type' => 'node',
            'id' => '1',
          ],
          'node:2' => [
            'entity_type' => 'node',
            'id' => '2',
          ],
        ],
        TRUE,
      ],
    ];
  }

  /**
   * @covers ::getReferencesWithoutTarget
   * @dataProvider providerGetReferencesWithoutTarget
   */
public function testGetReferencesWithoutTarget($getAllReferenceFields, $result) {
    $sut = $this->getMockBuilder(RadioactivityReferenceUpdater::class)
      ->onlyMethods([
        'getAllReferenceFields',
        'entitiesWithNonexistentFields',
      ])
      ->disableOriginalConstructor()
      ->getMock();

    // The return value of ::getAllReferenceFields are the configured fields
    // of entity+bundle that are configured as radioactivity reference fields.
    $sut->expects($this->any())
      ->method('getAllReferenceFields')
      ->will($this->returnValue($getAllReferenceFields));

    // The return value of ::entitiesWithNonexistentFields represents the
    // entities what have radioactivity reference fields with empty target, i.e.
    // a missing Radioactivity entity. This is the content that the updater
    // works with, which it has to fix.
    $sut->expects($this->any())
      ->method('entitiesWithNonexistentFields')
      ->will($this->returnValueMap([
        ['user', 'user', ['field_user_rar'], []],
        ['node', 'article', ['field_node_rar'], ['1']],
        ['node', 'page', ['field_node_rar'], []],
        ['taxonomy_term', 'tags', ['field_term_rar'], ['4', '5']],
      ]));

    $this->assertEquals($result, $sut->getReferencesWithoutTarget());
  }

  /**
   * Data provider for testGetReferencesWithoutTarget.
   *
   * @return array
   *   - ::getAllReferenceFields result value
   *   - ::getReferencesWithoutTarget result
   */
  public function providerGetReferencesWithoutTarget(): array {
    return [
      // No entities configured with radioactivity reference fields.
      [
        [],
        [],
      ],
      // An entity type that has no content without missing targets.
      [
        ['user' => ['user' => [0 => 'field_user_rar']]],
        [],
      ],
      // One entity type that has content without missing targets.
      [
        ['node' => ['article' => [0 => 'field_node_rar']]],
        [
          'node:1' => [
            'entity_type' => 'node',
            'id' => '1',
          ],
        ],
      ],
      // One entity type of which one bundle have content without missing
      // targets (article), the other has not (page).
      [
        [
          'node' => [
            'article' => [0 => 'field_node_rar'],
            'page' => [0 => 'field_node_rar'],
          ],
        ],
        [
          'node:1' => [
            'entity_type' => 'node',
            'id' => '1',
          ],
        ],
      ],
      // Two entity types that have content with missing targets.
      [
        [
          'node' => ['article' => [0 => 'field_node_rar']],
          'taxonomy_term' => ['tags' => [0 => 'field_term_rar']],
        ],
        [
          'node:1' => [
            'entity_type' => 'node',
            'id' => '1',
          ],
          'taxonomy_term:4' => [
            'entity_type' => 'taxonomy_term',
            'id' => '4',
          ],
          'taxonomy_term:5' => [
            'entity_type' => 'taxonomy_term',
            'id' => '5',
          ],
        ],
      ],
    ];
  }

  /**
   * @covers ::updateReferenceFields
   * @dataProvider providerUpdateReferenceFields
   */
  public function testUpdateReferenceFields($getReferenceFields, $fieldIsEmpty, $entityIsUpdated) {
    $sut = $this->getMockBuilder(RadioactivityReferenceUpdater::class)
      ->onlyMethods([
        'getReferenceFields',
        'createRadioactivity',
        'getRequestTime',
        'getFieldDefaultEnergy',
      ])
      ->disableOriginalConstructor()
      ->getMock();
    $fieldItemList = $this->createMock(FieldItemListInterface::class);
    $fieldItemList->expects($this->any())
      ->method('isEmpty')
      ->willReturn($this->returnValue($fieldIsEmpty));
    $langcode = 'nl';
    $fieldItemList->expects($this->any())
      ->method('getLangcode')
      ->willReturn($langcode);
    $fieldItemList->expects($entityIsUpdated ? $this->once() : $this->never())
      ->method('setValue');

    $entity = $this->prophesize(NodeInterface::class);
    $entity->getEntityTypeId()->willReturn('node');
    $entity->bundle()->willReturn('article');
    $entity->get('field_rar')->willReturn($fieldItemList);
    $entity->save()->shouldBeCalledTimes($entityIsUpdated ? 1 : 0);

    // The return value of ::getReferenceFields are the configured fields
    // of given entity+bundle that are configured as radioactivity reference
    // fields.
    $sut->expects($this->any())
      ->method('getReferenceFields')
      ->will($this->returnValue($getReferenceFields));

    // Testing that ::createRadioactivity is called is the only relevant part.
    // The values it is called with should match the return values of their
    // respective methods. Their value is not relevant here.
    $requestTime = 1641744951;
    $defaultEnergy = 3;
    $sut->expects($fieldIsEmpty ? $this->once() : $this->never())
      ->method('createRadioactivity')
      ->with($requestTime, $defaultEnergy, $langcode);

    $sut->expects($this->any())
      ->method('getRequestTime')
      ->will($this->returnValue($requestTime));

    $sut->expects($this->any())
      ->method('getFieldDefaultEnergy')
      ->will($this->returnValue($defaultEnergy));

    $this->assertEquals($entityIsUpdated, $sut->updateReferenceFields($entity->reveal()));
  }

  /**
   * Data provider for testUpdateReferenceFields.
   *
   * @return array
   *   - ::getReferenceFields result value
   *   - radioactiveReferenceField is empty
   *   - entity is updated
   */
  public function providerUpdateReferenceFields(): array {
    return [
      [
        // The entity does not contains radioactivity reference fields.
        [],
        FALSE,
        FALSE,
      ],
      [
        // The entity contains a non-empty radioactivity reference field.
        ['field_rar'],
        FALSE,
        FALSE,
      ],
      [
        // The entity contains an empty radioactivity reference field.
        ['field_rar'],
        TRUE,
        TRUE,
      ],
    ];
  }

}
