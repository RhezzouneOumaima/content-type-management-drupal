<?php

namespace Drupal\Tests\radioactivity\Unit;

/**
 * Checks that the event "radioactivity_field_cutoff" is correctly defined.
 *
 * @coversDefaultClass \Drupal\radioactivity\Event\EnergyBelowCutoffEvent
 * @group radioactivity
 *
 * @requires module rules
 */
class EnergyBelowCutoffTest extends EventTestBase {

  /**
   * Tests the event metadata.
   */
  public function testBundleCreatedEvent() {
    $plugin_definition = $this->eventManager->getDefinition('radioactivity_field_cutoff');
    $this->assertSame('Energy is below the cutoff level', (string) $plugin_definition['label']);

    $event = $this->eventManager->createInstance('radioactivity_field_cutoff');
    $entity_context_definition = $event->getContextDefinition('entity');
    $this->assertSame('entity', $entity_context_definition->getDataType());
    $this->assertSame('Entity', $entity_context_definition->getLabel());
  }

}
