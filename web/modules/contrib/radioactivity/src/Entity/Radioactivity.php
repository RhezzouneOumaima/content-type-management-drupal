<?php

namespace Drupal\radioactivity\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Language\LanguageInterface;
use Drupal\radioactivity\RadioactivityInterface;

/**
 * Defines the radioactivity entity class.
 *
 * @ContentEntityType(
 *   id = "radioactivity",
 *   label = @Translation("Radioactivity"),
 *   handlers = {
 *     "storage" = "Drupal\radioactivity\RadioactivityStorage",
 *     "views_data" = "Drupal\radioactivity\RadioactivityViewsData",
 *   },
 *   base_table = "radioactivity",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "id",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class Radioactivity extends ContentEntityBase implements RadioactivityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['timestamp'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Timestamp'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDescription(t('The time this radioactivity energy was created or decayed.'));

    $fields['energy'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Energy'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDescription(t('The radioactivity energy level.'));

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDescription(t('The content language this radioactivity energy belongs to.'))
      ->setDefaultValue(LanguageInterface::LANGCODE_NOT_SPECIFIED);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode(): string {
    return $this->get('langcode')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLangcode(string $langcode) {
    $this->set('langcode', $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function getTimestamp(): int {
    return $this->get('timestamp')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTimestamp(int $timestamp) {
    $this->set('timestamp', $timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function getEnergy(): float {
    return $this->get('energy')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEnergy(float $value) {
    $this->set('energy', $value);
  }

}
