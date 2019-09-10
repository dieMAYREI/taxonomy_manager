<?php
/**
 * Created by PhpStorm.
 * User: kacper.ziolkowski
 * Date: 21.12.2017
 * Time: 16:26
 */

namespace Drupal\taxonomy_manager\Service;

use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Cache\Cache;

class TaxonomyManagerMergeService
{
  /**
   * @var TaxonomyManagerService mixed
   */
  protected $service;

  /**
   * MergeTermsService constructor.
   */
  public function __construct()
  {
    $this->service = \Drupal::service('taxonomy_manager.manager_service');
  }

  /**
   * @param array $tids
   * @param       $newName
   * @param       $targetTid
   *
   * @throws \Exception
   */
  public function mergeTerms(array $tids, $newName, $targetTid)
  {
    /** @var Term $term */
    $term = Term::load($targetTid);

    if (!$term instanceof Term) {
      throw new \RuntimeException('Term not found');
    }

    $this->updateTaxonomyIndexTable($tids, $targetTid);

    $this->renameTerm($newName, $term);

    $this->updateReferences($tids, $targetTid);

    $this->deleteOtherTerms($tids, $targetTid);

    drupal_set_message('Merge succesfully!', 'status', true);
  }

  /**
   * @param $tids
   * @param $targetTid
   */
  public function updateTaxonomyIndexTable($tids, $targetTid) {
    if (($key = array_search($targetTid, $tids)) !== FALSE) {
      unset($tids[$key]);
    }

    /** @var \Drupal\Core\Database\Connection $db */
    $db = \Drupal::database();

    foreach ($tids as $tid) {
      /**
       * Select the affected nid`s
       */
      $query = $db->select('taxonomy_index', 'ti');
      $query->fields('ti', ['nid']);
      $query->condition('ti.tid', $tid, '=');
      $query->groupBy('ti.nid');
      $result = $query->execute();
      /** @var \stdClass $record */
      foreach ($result as $record) {
        /**
         * Select the affected nid`s
         */
        $query = $db->select('taxonomy_index', 'ti');
        $query->fields('ti', ['nid', 'tid']);
        $query->condition('ti.nid', $record->nid, '=');
        $resultNid = $query->execute();
        /** @var \stdClass $record */
        foreach ($resultNid as $recordNid) {
          echo $recordNid->tid.'--'.$targetTid.'--'.$recordNid->nid.'\r\n<br>';
          if ($recordNid->tid == $targetTid) {
            $query = $db->delete('taxonomy_index');
            $query->condition('tid', $targetTid, '=');
            $query->condition('nid', $recordNid->nid, '=');
            $query->execute();
          }
        }
      }
      $db->update('taxonomy_index')
        ->fields(['tid' => $targetTid])
        ->condition('tid', $tid, "=")
        ->execute();
    }
  }

  /**
   * @param                              $newName
   * @param \Drupal\taxonomy\Entity\Term $targetTerm
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function renameTerm($newName, Term $targetTerm)
  {
    $targetTerm->setName($newName);
    $targetTerm->save();
  }

  /**
   * @param array                        $tids
   * @param                              $targetTid
   *
   * @throws \Drupal\Core\Database\InvalidQueryException
   */
  private function updateReferences(array $tids, $targetTid)
  {
    /** Clear cache */
    foreach (Cache::getBins() as $service_id => $cache_backend) {
      $cache_backend->deleteAll();
    }

    foreach ($tids as $tid) {

      if ($tid === $targetTid) {
        continue;
      }

      $tables = $this->service->getTablesToUpdate();

      foreach ($tables as $table => $field) {
        /** @var $query_updateTables */
        $query_updateTables = \Drupal::database()->update($table);
        $query_updateTables->fields([$field => $targetTid]);
        $query_updateTables->condition($field, $tid);
        $query_updateTables->execute();
      }
    }
  }

  /**
   * @param array $tids
   * @param       $targetTid
   */
  private function deleteOtherTerms(array $tids, $targetTid)
  {
    foreach ($tids as $tid) {
      if ($tid !== $targetTid) {
        Term::load($tid)->delete();
      }
    }
  }

  /**
   * @param $query
   *
   * @return array
   */
  public function getMergeableTerms($query)
  {
    $tids = [];

    /** @var array $arraySelect */
    $arraySelect = [];

    /** @var array $array_elements */
    $array_elements = $this->service->getMultipleTidNames($query);

    /* Adds arraySelect the value with key in () */
    foreach ($array_elements as $key => $value) {

      if ($key !== '') {
        $tids[]            = $key;
        $arraySelect[$key] = $value . ' ( tid: ' . $key . ' )';
      }
    }

    return [$arraySelect, $tids];
  }

  /**
   * @param string $newName
   *
   * @return int
   */
  public function checkIfNameAlreadyExists($newName, $vid)
  {
    /** @var  $query_all */
    $query_all = \Drupal::entityQuery('taxonomy_term');
    $query_all->condition(
      'name',
      \Drupal::database()->escapeLike($newName),
      'LIKE'
    );
    $query_all->condition(
      'vid',
      $vid,
      '='
    );
    $name = $query_all->execute();

    return !empty($name);
  }
}
