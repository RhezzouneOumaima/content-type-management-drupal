<?php

namespace Drupal\Tests\radioactivity\Unit;

use Drupal\rules\Core\RulesEventManager;
use Drupal\Tests\rules\Unit\Integration\Event\EventTestBase as RulesEventTestBase;

/**
 * Base class containing common code for radioactivity event tests.
 *
 * @group radioactivity
 *
 * @requires module rules
 */
abstract class EventTestBase extends RulesEventTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Must enable our module to make our plugins discoverable.
    $this->enableModule('radioactivity', [
      'Drupal\\radioactivity' => __DIR__ . '/../../../src',
    ]);

    // Tell the plugin manager where to look for plugins.
    $this->moduleHandler->getModuleDirectories()
      ->willReturn(['radioactivity' => __DIR__ . '/../../../']);

    // Create a real plugin manager with a mock moduleHandler.
    $this->eventManager = new RulesEventManager($this->moduleHandler->reveal(), $this->entityTypeBundleInfo->reveal());
  }

}
