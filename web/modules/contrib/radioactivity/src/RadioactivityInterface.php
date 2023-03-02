<?php

namespace Drupal\radioactivity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a radioactivity entity type.
 */
interface RadioactivityInterface extends ContentEntityInterface {

  /**
   * Returns the language code.
   *
   * @return string
   *   The language code.
   */
  public function getLangcode(): string;

  /**
   * Sets the language code.
   *
   * @param string $langcode
   *   The language code.
   */
  public function setLangcode(string $langcode);

  /**
   * Returns the timestamp value.
   *
   * @return int
   *   The timestamp value.
   */
  public function getTimestamp(): int;

  /**
   * Sets the timestamp value.
   *
   * @param int $timestamp
   *   The timestamp value.
   */
  public function setTimestamp(int $timestamp);

  /**
   * Returns the energy value.
   *
   * @return float
   *   The energy value.
   */
  public function getEnergy(): float;

  /**
   * Sets the energy value.
   *
   * @param float $value
   *   The energy value.
   */
  public function setEnergy(float $value);

}
