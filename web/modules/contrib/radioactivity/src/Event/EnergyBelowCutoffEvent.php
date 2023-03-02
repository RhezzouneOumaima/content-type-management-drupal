<?php

namespace Drupal\radioactivity\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Represents a 'Energy is below the cutoff level' event.
 *
 * @see rules_entity_presave()
 */
class EnergyBelowCutoffEvent extends Event {

  const EVENT_NAME = 'radioactivity_field_cutoff';

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  public ContentEntityInterface $entity;

  /**
   * Constructs the object.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   */
  public function __construct(ContentEntityInterface $entity) {
    $this->entity = $entity;
  }

}
