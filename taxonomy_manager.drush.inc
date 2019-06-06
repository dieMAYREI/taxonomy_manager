<?php
/**
* @return array
*/
function taxonomy_manager_drush_command()
{
  $items = [];
  $items['update-taxonomy'] = [
    'description' => 'Updates the Taxonomy Table for missing Tags',
    'arguments' => [],
    //'drupal dependencies' => ['custom_drush_command'],
    'aliases' => ['updateTaxonomyIndex'],
  ];

  return $items;
}

function drush_taxonomy_manager_update_taxonomy() {

  $tables = getTablesToUpdate();

  $db = \Drupal::database();

  foreach ($tables as $key => $table) {
    $query = $db->select($key, 'xx');
    $query->join('node_field_data', 'nfd', 'nfd.nid = xx.entity_id');
    $query->fields('xx');
    $query->fields('nfd');
    $results = $query->execute();

    $key = str_replace(['media__', 'node__'], '', $key);
    $target_id_field = $key . '_target_id';

    foreach ($results as $result) {
      $terms_to_insert = check_taxonomie_index($result->entity_id, $result->$target_id_field, $result->changed);

      if($terms_to_insert){
        $db->insert('taxonomy_index')
          ->fields(['nid', 'tid', 'status', 'sticky', 'created'],
            [$terms_to_insert['node_id'], $terms_to_insert['term_id'], 1, 0, $terms_to_insert['changed']])
          ->execute();
      }
    }
  }
}

/**
 * @return array
 * @throws \Drupal\Core\Database\InvalidQueryException
 */
function getTablesToUpdate()
{
  /** @var $query_references */
  $query_references = \Drupal::database()->select('config', 'data');
  $query_references->condition(
    'data', '%' . $query_references->escapeLike(
      '{s:11:"target_type";s:13:"taxonomy_term";}'
    ) . '%', 'LIKE'
  );
  $query_references->fields('data', ['data']);

  $rows = $query_references->execute()->fetchAll();

  $return = [];

  /** @var \stdClass $row */
  foreach ($rows as $row) {

    $data = unserialize($row->data);

    $return[$data['entity_type'] . '__' . $data['field_name']]
      = $data['field_name'] . '_target_id';
  }

  return $return;
}

/**
 * @param $node_id
 * @param $term_id
 * @param $changed
 *
 * @return array
 */
function check_taxonomie_index($node_id,$term_id,$changed){
  $return = [];
  $db = \Drupal::database();
  $query = $db->select('taxonomy_index', 'ti');
  $query->condition('tid', $term_id);
  $query->condition('nid', $node_id);
  $query->fields('ti');
  $records = $query->execute();

  $return['node_id'] = $node_id;
  $return['term_id'] = $term_id;
  $return['changed'] = $changed;

  foreach ($records as $record){
    $return = [];
  }

  return $return;
}
