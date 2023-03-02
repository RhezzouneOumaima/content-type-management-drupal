<?php

namespace Drupal\Tests\radioactivity\Traits;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\radioactivity\DefaultIncidentStorage;
use Drupal\radioactivity\Entity\Radioactivity;
use Drupal\radioactivity\RadioactivityInterface;

/**
 * Radioactivity functional test trait.
 */
trait RadioactivityFunctionTestTrait {

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType = 'entity_test';

  /**
   * The entity type bundle.
   *
   * @var string
   */
  protected $entityBundle = 'entity_test';

  /**
   * Adds a Count type energy field to the content type.
   *
   * @param string $fieldName
   *   Field machine name.
   * @param int|float $defaultEnergy
   *   Default energy level.
   * @param int $cardinality
   *   Field cardinality.
   */
  public function addCountEnergyField($fieldName, $defaultEnergy = 0, $cardinality = 1) {

    $granularity = $halfLifeTime = $cutoff = 0;
    $this->createEnergyField($fieldName, 'count', $defaultEnergy, $granularity, $halfLifeTime, $cutoff, $cardinality);
  }

  /**
   * Adds a Linear type energy field to the content type.
   *
   * @param string $fieldName
   *   Field machine name.
   * @param int|float $defaultEnergy
   *   Field energy when the entity is created.
   * @param int $granularity
   *   Energy decay granularity.
   * @param int|float $cutoff
   *   Energy cut off value.
   * @param int $cardinality
   *   Field cardinality.
   */
  public function addLinearEnergyField($fieldName, $defaultEnergy = 0, $granularity = 900, $cutoff = 10, $cardinality = 1) {

    $halfLifeTime = 0;
    $this->createEnergyField($fieldName, 'linear', $defaultEnergy, $granularity, $halfLifeTime, $cutoff, $cardinality);
  }

  /**
   * Adds a Decay type energy field to the content type.
   *
   * @param string $fieldName
   *   Field machine name.
   * @param int|float $defaultEnergy
   *   Field energy when the entity is created.
   * @param int $granularity
   *   Energy decay granularity.
   * @param int $halfLifeTime
   *   Half-life time.
   * @param int|float $cutoff
   *   Energy cut off value.
   * @param int $cardinality
   *   Field cardinality.
   */
  public function addDecayEnergyField($fieldName, $defaultEnergy = 0, $granularity = 0, $halfLifeTime = 43200, $cutoff = 10, $cardinality = 1) {

    $this->createEnergyField($fieldName, 'decay', $defaultEnergy, $granularity, $halfLifeTime, $cutoff, $cardinality);
  }

  /**
   * Adds an radioactivity energy field to the content type.
   *
   * @param string $fieldName
   *   Field machine name.
   * @param string $profile
   *   Profile type.
   * @param int|float $defaultEnergy
   *   Field energy when the entity is created.
   * @param int $granularity
   *   Energy decay granularity.
   * @param int $halfLifeTime
   *   Half life time.
   * @param int|float $cutoff
   *   Energy cut off value.
   * @param int $cardinality
   *   Field cardinality.
   */
  protected function createEnergyField($fieldName, $profile, $defaultEnergy = 0, $granularity = 900, $halfLifeTime = 43200, $cutoff = 10, $cardinality = 1) {

    FieldStorageConfig::create([
      'entity_type' => $this->entityType,
      'type' => 'radioactivity',
      'field_name' => $fieldName,
      'cardinality' => $cardinality,
      'settings' => [
        'profile' => $profile,
        'granularity' => $granularity,
        'halflife' => $halfLifeTime,
        'cutoff' => $cutoff,
      ],
    ])->save();

    FieldConfig::create([
      'entity_type' => $this->entityType,
      'bundle' => $this->entityBundle,
      'field_name' => $fieldName,
      'label' => 'Radioactivity',
      'required' => TRUE,
      'default_value' => [
        [
          'energy' => $defaultEnergy,
          'timestamp' => 0,
        ],
      ],
    ])->save();
  }

  /**
   * Adds an radioactivity energy field to the content type.
   *
   * @param string $fieldName
   *   Field machine name.
   * @param string $profile
   *   Profile type.
   * @param int|float $defaultEnergy
   *   Field energy when the entity is created.
   * @param int $granularity
   *   Energy decay granularity.
   * @param int $halfLifeTime
   *   Half life time.
   * @param int|float $cutoff
   *   Energy cut off value.
   * @param int $cardinality
   *   Field cardinality.
   */
  protected function createReferenceEnergyField($fieldName, $profile, $defaultEnergy = 0, $granularity = 900, $halfLifeTime = 43200, $cutoff = 10, $cardinality = 1) {

    FieldStorageConfig::create([
      'entity_type' => $this->entityType,
      'type' => 'radioactivity_reference',
      'field_name' => $fieldName,
      'cardinality' => $cardinality,
      'settings' => [
        'profile' => $profile,
        'granularity' => $granularity,
        'halflife' => $halfLifeTime,
        'cutoff' => $cutoff,
      ],
    ])->save();

    FieldConfig::create([
      'entity_type' => $this->entityType,
      'bundle' => $this->entityBundle,
      'field_name' => $fieldName,
      'required' => TRUE,
      'settings' => [
        'handler' => 'default:radioactivity',
        'handler_settings' => ['auto_create' => FALSE],
        'default_energy' => $defaultEnergy,
      ],
    ])->save();
  }

  /**
   * Creates an energy field formatter.
   *
   * @param string $fieldName
   *   Field machine name.
   */
  protected function createEnergyFormDisplay($fieldName) {
    $entityFormDisplay = EntityFormDisplay::load('entity_test.entity_test.default');
    $entityFormDisplay->setComponent($fieldName, [
      'type' => 'radioactivity_energy',
    ]);
    $entityFormDisplay->save();
  }

  /**
   * Creates an emitter field formatter.
   *
   * @param string $fieldName
   *   Field machine name.
   * @param int|float $energy
   *   The energy to emit.
   * @param string $display
   *   The field display type.
   *
   * @return \Drupal\Core\Entity\Entity\EntityViewDisplay
   *   The entity view display object.
   */
  protected function createEmitterViewDisplay($fieldName, $energy = 10, $display = TRUE) {
    $entity_view_display = EntityViewDisplay::create([
      'targetEntityType' => $this->entityType,
      'bundle' => $this->entityBundle,
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $entity_view_display->setComponent($fieldName, [
      'type' => 'radioactivity_emitter',
      'settings' => [
        'energy' => $energy,
        'display' => $display,
      ],
    ]);
    $entity_view_display->save();
    return $entity_view_display;
  }

  /**
   * Creates a Radioactivity field value formatter.
   *
   * @param string $fieldName
   *   Field machine name.
   * @param int $decimals
   *   Number of decimals to display.
   *
   * @return \Drupal\Core\Entity\Entity\EntityViewDisplay
   *   The entity view display object.
   */
  protected function createValueViewDisplay($fieldName, $decimals = 0) {
    $entity_view_display = EntityViewDisplay::create([
      'targetEntityType' => $this->entityType,
      'bundle' => $this->entityBundle,
      'mode' => 'default',
    ]);
    $entity_view_display->setComponent($fieldName, [
      'type' => 'radioactivity_value',
      'settings' => ['decimals' => $decimals],
    ]);
    $entity_view_display->save();
    return $entity_view_display;
  }

  /**
   * Creates an Radioactivity Reference field value formatter.
   *
   * @param string $fieldName
   *   Field machine name.
   * @param int $decimals
   *   Number of decimals to display.
   *
   * @return \Drupal\Core\Entity\Entity\EntityViewDisplay
   *   The entity view display object.
   */
  protected function createReferenceValueViewDisplay($fieldName, $decimals = 0) {
    $entity_view_display = EntityViewDisplay::create([
      'targetEntityType' => $this->entityType,
      'bundle' => $this->entityBundle,
      'mode' => 'default',
    ]);
    $entity_view_display->setComponent($fieldName, [
      'type' => 'radioactivity_value',
      'settings' => ['decimals' => $decimals],
    ]);
    $entity_view_display->save();
    return $entity_view_display;
  }

  /**
   * Set the entity type.
   *
   * @param string $type
   *   The entity type.
   */
  public function setEntityType($type) {
    $this->entityType = $type;
  }

  /**
   * Set the entity bundle.
   *
   * @param string $bundle
   *   The entity bundle.
   */
  public function setEntityBundle($bundle) {
    $this->entityBundle = $bundle;
  }

  /**
   * Sets the emitter energy of a field.
   *
   * @param string $fieldName
   *   The field name.
   * @param int $energy
   *   The energy value to set.
   */
  public function setFieldEmitterEnergy($fieldName, $energy = 10) {
    $this->updateFieldEmitterSettings($fieldName, ['energy' => $energy]);
  }

  /**
   * Sets the emitter display mode of a field.
   *
   * @param string $fieldName
   *   The field name.
   * @param bool $displayEnergy
   *   Whether to display the energy level.
   */
  public function setFieldEmitterDisplay($fieldName, $displayEnergy = FALSE) {
    $display = $displayEnergy;
    $this->updateFieldEmitterSettings($fieldName, ['display' => $display]);
  }

  /**
   * Updates the emitter field display settings.
   *
   * @param string $fieldName
   *   The field name.
   * @param array $settings
   *   Allowed keys:
   *   'energy': The energy value this field will emit when displayed.
   *   'display': True if the energy value is visible.
   */
  protected function updateFieldEmitterSettings($fieldName, array $settings) {

    $display = EntityViewDisplay::load('entity_test.entity_test.default');
    $component = $display->getComponent($fieldName);

    foreach ($settings as $key => $value) {
      $component['settings'][$key] = $value;
    }
    $display->setComponent($fieldName, $component)
      ->save();
  }

  /**
   * Creates an entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The created entity.
   */
  public function createContent() {

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = \Drupal::entityTypeManager()->getStorage($this->entityType)->create([
      'type' => $this->entityType,
      'title' => $this->randomString(),
    ]);
    $entity->save();

    return $entity;
  }

  /**
   * Assert the energy values from a field.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The host entity of the field.
   * @param string $fieldName
   *   The field to be asserted.
   * @param array|string|int $expectedValues
   *   The expected field values.
   * @param string $operator
   *   The operator to be used to compare. Allowed values: '>', '>=', '<', '<=',
   *   '=='. The actual value on the left, the expected on the right.
   * @param string $message
   *   The assertion message.
   */
  public function assertFieldEnergyValue(EntityInterface $entity, $fieldName, $expectedValues, $operator = '==', $message = '') {
    $expectedValues = is_array($expectedValues) ? $expectedValues : [$expectedValues];
    $actualValues = array_map(
      function ($item) {
        return $item['energy'];
      },
      $entity->get($fieldName)->getValue()
    );

    $this->assertEnergyValues($fieldName, $actualValues, $expectedValues, $operator, $message);
  }

  /**
   * Assert the energy values from the page.
   *
   * @param string $fieldName
   *   The field to be asserted.
   * @param array|string|int $expectedValues
   *   The expected field values.
   * @param string $operator
   *   The operator to be used to compare. Allowed values: '>', '>=', '<', '<=',
   *   '=='. The actual value on the left, the expected on the right.
   * @param string $message
   *   The assertion message.
   */
  public function assertPageEnergyValue($fieldName, $expectedValues, $operator = '==', $message = '') {
    $expectedValues = is_array($expectedValues) ? $expectedValues : [$expectedValues];
    $actualValues = $this->getPageEnergyValues($fieldName);

    $this->assertEnergyValues($fieldName, $actualValues, $expectedValues, $operator, $message);
  }

  /**
   * Assert field energy values.
   *
   * @param string $fieldName
   *   The field to be asserted.
   * @param array $actualValues
   *   The actual field values.
   * @param array $expectedValues
   *   The expected field values.
   * @param string $operator
   *   The operator to be used to compare. Allowed values: '>', '>=', '<', '<=',
   *   '=='. The actual value on the left, the expected on the right.
   * @param string $message
   *   The assertion message.
   */
  private function assertEnergyValues($fieldName, array $actualValues, array $expectedValues, $operator = '==', $message = '') {
    if (array_diff(array_keys($actualValues), array_keys($expectedValues))) {
      throw new \RuntimeException(sprintf('Invalid number of expected values for %s.', $fieldName));
    }

    foreach ($actualValues as $key => $actual) {
      $expected = $expectedValues[$key];

      switch ($operator) {
        case '>':
          $result = $actual > $expected;
          break;

        case '>=':
          $result = $actual >= $expected;
          break;

        case '<':
          $result = $actual < $expected;
          break;

        case '<=':
          $result = $actual <= $expected;
          break;

        case '==':
        default:
          $result = $actual == $expected;
      }
      $message = $message ?: $message = sprintf('The energy value of %s is %s, but %s expected.', $fieldName, $actual, $expected);
      $this->assertTrue($result, $message);
    }
  }

  /**
   * Gets the field's energy values from the session's page.
   *
   * @param string $fieldName
   *   The name of the field to be asserted.
   *
   * @return array
   *   The field values.
   */
  public function getPageEnergyValues($fieldName) {
    $values = [];
    $fieldBaseName = substr($fieldName, 6);
    $selector = '.field--name-field-' . $fieldBaseName . ' .field__item';

    $rows = $this->getSession()->getPage()->findAll('css', $selector);
    if ($rows) {
      foreach ($rows as $row) {
        $values[] = $row->getHtml();
      }
    }

    return $values;
  }

  /**
   * Asserts the actual incident count.
   *
   * @param int $expected
   *   The expected count.
   * @param string $message
   *   The assertion message.
   */
  public function assertIncidentCount($expected, $message = '') {
    $actual = $this->getIncidentCount();
    $message = $message ?: $message = sprintf('The incident count is %s, but %s expected.', $actual, $expected);
    $this->assertTrue($actual == $expected, $message);
  }

  /**
   * Gets the number of incidents from the incident storage.
   *
   * @return int
   *   The incident count.
   */
  public function getIncidentCount() {
    $storage = \Drupal::service('radioactivity.default_incident_storage');
    return count($storage->getIncidents());
  }

}
