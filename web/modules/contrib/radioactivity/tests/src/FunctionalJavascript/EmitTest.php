<?php

namespace Drupal\Tests\radioactivity\FunctionalJavascript;

use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Testing the energy emission of the radioactivity field.
 *
 * @see https://www.drupal.org/docs/8/phpunit/phpunit-javascript-testing-tutorial
 *
 * @group radioactivity
 */
class EmitTest extends RadioactivityFunctionalJavascriptTestBase {

  use CronRunTrait;

  /**
   * The name of the energy field.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * Tests Basic Radioactivity count functionality.
   */
  public function testCount() {
    $this->fieldName = 'field_count_energy';
    $this->setEntityType('entity_test');
    $this->setEntityBundle('entity_test');
    $this->addCountEnergyField($this->fieldName);
    $this->createEnergyFormDisplay($this->fieldName);
    $this->createEmitterViewDisplay($this->fieldName, 1, TRUE);

    $entity = $this->createContent();
    $this->assertIncidentCount(0);

    $this->drupalGet($entity->toUrl());
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertIncidentCount(1);
    $this->assertPageEnergyValue($this->fieldName, 0);
    $this->assertFieldEnergyValue($entity, $this->fieldName, 0);

    // Run cron from the browser.
    $this->cronRun();

    // The entity has updated values, reload it.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = \Drupal::entityTypeManager()
      ->getStorage($this->entityType)
      ->load($entity->id());

    $this->assertIncidentCount(0);
    $this->assertFieldEnergyValue($entity, $this->fieldName, 1);

    $this->drupalGet($entity->toUrl());
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertPageEnergyValue($this->fieldName, 1);
  }

  /**
   * Validate that no energy is emitted if entity is unpublished.
   */
  public function testUnpublished() {
    $this->fieldName = 'field_count_energy';
    $this->setEntityType('entity_test_revpub');
    $this->setEntityBundle('entity_test_revpub');
    $this->addCountEnergyField($this->fieldName);
    $this->createEnergyFormDisplay($this->fieldName);
    $this->createEmitterViewDisplay($this->fieldName, 1, TRUE);

    /** @var \Drupal\entity_test\Entity\EntityTestRevPub $entity */
    $entity = $this->createContent();
    $this->assertIncidentCount(0);

    // No emit when unpublished.
    $entity->setUnpublished();
    $entity->save();

    $this->drupalGet('/entity_test_rev/manage/' . $entity->id());
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertIncidentCount(0);
  }

}
