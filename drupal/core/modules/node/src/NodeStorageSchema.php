<?php

/**
 * @file
 * Contains \Drupal\node\NodeStorageSchema.
 */

namespace Drupal\node;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the node schema handler.
 */
class NodeStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    // Marking the respective fields as NOT NULL makes the indexes more
    // performant.
    $schema['node_field_data']['fields']['default_langcode']['not null'] = TRUE;
    $schema['node_field_revision']['fields']['default_langcode']['not null'] = TRUE;

    $schema['node_field_data']['indexes'] += array(
      'node__default_langcode' => array('default_langcode'),
      'node__frontpage' => array('promote', 'status', 'sticky', 'created'),
      'node__status_type' => array('status', 'type', 'nid'),
      'node__title_type' => array('title', array('type', 4)),
    );

    $schema['node_field_revision']['indexes'] += array(
      'node__default_langcode' => array('default_langcode'),
    );

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == 'node_revision') {
      switch ($field_name) {
        case 'langcode':
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;

        case 'revision_uid':
          $this->addSharedTableFieldForeignKey($storage_definition, $schema, 'users', 'uid');
          break;
      }
    }

    if ($table_name == 'node_field_data') {
      switch ($field_name) {
        case 'promote':
        case 'status':
        case 'sticky':
        case 'title':
          // Improves the performance of the indexes defined
          // in getEntitySchema().
          $schema['fields'][$field_name]['not null'] = TRUE;
          break;

        case 'changed':
        case 'created':
        case 'langcode':
          // @todo Revisit index definitions:
          //   https://www.drupal.org/node/2015277.
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;
      }
    }

    if ($table_name == 'node_field_revision') {
      switch ($field_name) {
        case 'langcode':
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;
      }
    }

    return $schema;
  }

}
