<?php

/**
 * @file
 * Views hook implementations for the radioactivity module.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data().
 */
function radioactivity_field_views_data(FieldStorageConfigInterface $field_storage) {
  $data = views_field_default_views_data($field_storage);

  if ($field_storage->getType() !== 'radioactivity_reference') {
    return $data;
  }

  $entity_type_manager = Drupal::entityTypeManager();
  $entity_type_id = $field_storage->getTargetEntityTypeId();
  /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
  $table_mapping = $entity_type_manager->getStorage($entity_type_id)->getTableMapping();

  foreach ($data as $table_name => $table_data) {
    // Add a relationship to the radioactivity entity.
    $entity_type_id = $field_storage->getTargetEntityTypeId();
    $entity_type = $entity_type_manager->getDefinition($entity_type_id);
    $field_name = $field_storage->getName();

    // Provide a relationship for the entity type with the radioactivity
    // reference field.
    $args = [
      '@label' => t('Radioactivity'),
      '@field_name' => $field_name,
    ];
    $data[$table_name][$field_name]['relationship'] = [
      'title' => t('Radioactivity referenced from @field_name', $args),
      'label' => t('@field_name: Radioactivity', $args),
      'group' => $entity_type->getLabel(),
      'help' => t('Appears in: @bundles.', ['@bundles' => implode(', ', $field_storage->getBundles())]),
      'id' => 'standard',
      'base' => 'radioactivity',
      'entity type' => 'radioactivity',
      'base field' => 'id',
      'relationship field' => $field_name . '_target_id',
      'extra' => [
        [
          'field' => 'langcode',
          'left_field' => 'langcode',
        ],
      ],
    ];

    // Provide a reverse relationship for the entity type that is referenced by
    // the field.
    $args['@entity'] = $entity_type->getLabel();
    $pseudo_field_name = 'reverse__' . $entity_type_id . '__' . $field_name;
    $data['radioactivity'][$pseudo_field_name]['relationship'] = [
      'title' => t('@entity using @field_name', $args),
      'label' => t('@field_name', ['@field_name' => $field_name]),
      'group' => t('Radioactivity'),
      'help' => t('Relate each @entity with a @field_name set to Radioactivity.', $args),
      'id' => 'entity_reverse',
      'base' => $entity_type->getDataTable() ?: $entity_type->getBaseTable(),
      'entity_type' => $entity_type_id,
      'base field' => $entity_type->getKey('id'),
      'field_name' => $field_name,
      'field table' => $table_mapping->getDedicatedDataTableName($field_storage),
      'field field' => $field_name . '_target_id',
      'join_extra' => [
        [
          'field' => 'deleted',
          'value' => 0,
          'numeric' => TRUE,
        ],
      ],
    ];
  }

  return $data;
}
