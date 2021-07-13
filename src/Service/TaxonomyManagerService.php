<?php

namespace Drupal\taxonomy_manager\Service;

use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\TermInterface;

/**
 * Class TaxonomyManagerService
 *
 * @package Drupal\taxonomy_manager\Service
 */
class TaxonomyManagerService
{
    use StringTranslationTrait;

    /** @var integer $results_pro_page */
    private $results_pro_page = 100;

    /** @var integer $results_total */
    private $results_total;

    private $pager;

    /**
     * @param $values
     * @param $vid
     *
     * @return array
     * @throws \Drupal\Core\Database\InvalidQueryException
     */
    public function getResults($values, $vid)
    {
        /** @var $return */
        $return = [];

        /** @var array $vids */
        $vids = $this->getVids();

        /** @var $suchValues */
        $suchValues = trim($values);

        $suchArray = '';

        /** @var $suchArray */
        if ($suchValues) {
            $suchArray = explode(',', $suchValues);
        }

        /** @var \Drupal\Core\Database\Query\Select $query_all */
        $query_all = \Drupal::database()
            ->select('taxonomy_term_field_data', 'ttfd');

        $query_all->fields('ttfd', ['name', 'tid', 'vid', 'langcode']);

        /** Sort rows by header (tid) */
        //$query_all->tableSort($this->header);
        $sort  = \Drupal::request()->get('sort');
        $order = \Drupal::request()->get('order'); // form param
        //$vid   = \Drupal::request()->get('vid'); // form param
        if (!$sort) {
            $sort = 'asc';
        }
        if (!$order) {
            $order = 'tid';
        }
        if (!$vid) {
            $vid = 'copyright';
        }

        $query_all->orderBy($order, $sort);

        /** Query_all like the vid, if vid is not empty */
        if ($vid !== '') {

            $query_all->condition('vid', $vid);

        } else {
            /**
             * Set default value with first element of the array
             *
             * @var $vids
             */

            $query_all->condition('vid', reset($vids));
        }

        /** @var $group */
        $group = $query_all->orConditionGroup();

        /** Get the values like the searchString */
        if (is_array($suchArray)) {

            foreach ($suchArray as $searchString) {
                $searchString = trim($searchString);
                $charSafeSearchString = iconv('UTF-8', 'ASCII//TRANSLIT', $searchString);
                $group->condition(
                    'name', '%' . \Drupal::database()
                        ->escapeLike($searchString) . '%', 'LIKE'
                );
                $group->condition(
                    'tid', '%' . \Drupal::database()
                        ->escapeLike($searchString) . '%', 'LIKE'
                );
                $group->condition(
                    'vid', '%' . \Drupal::database()
                        ->escapeLike($charSafeSearchString) . '%', 'LIKE'
                );
                $group->condition(
                    'langcode', '%' . \Drupal::database()
                        ->escapeLike($charSafeSearchString) . '%', 'LIKE'
                );
            }

            $query_all->condition($group);
        }

        /** @var $totalCountTerms */
        $totalCountTerms = $query_all->countQuery()->execute()->fetchField();

        $this->results_total = $totalCountTerms;

        /** @var $page */
        $page = \Drupal::service('pager.manager')->createPager($this->results_total, $this->results_pro_page);

        /** @var $offset */
        $offset = $this->results_pro_page * $page->getCurrentPage();

        /** @var $results */
        $results = $query_all->range($offset, $this->results_pro_page)->execute(
        );

        /**
         * Shows the number of actual paged terms from total terms
         *
         * @var  $end
         */
        $end = $offset + $this->results_pro_page;

        /** @var $begin */
        if ($totalCountTerms === 0) {

            $begin = $offset;

        } else {

            $begin = $offset + 1;
        }

        if ($end < $totalCountTerms) {

            $this->total = $begin . ' - ' . $end . ' / ' . $totalCountTerms;
        } else {

            $this->total = $begin . ' - ' . $totalCountTerms . ' / '
                . $totalCountTerms;
        }

        /**
         * Build rows
         */
        foreach ($results as $row) {

            /** @var Term $term */
            $term = Term::load($row->tid);

            /** @var $my_array */
            $my_array = [];
            if (null !== $term) {
                $my_array['tid'] = $term->id();
            }
            $my_array['name']     = $term->getName();
            $my_array['langcode'] = $term->get('langcode')->getLangcode();
            $my_array['vid']      = \Drupal\taxonomy\Entity\Term::load($row->tid)->get('vid')->getString();

            $my_array['operationen'] = [
                'data' => [
                    '#type'  => 'dropbutton',
                    '#links' => [
                        'edit'   => [
                            'title' => $this->t('Bearbeiten'),
                            'url'   => Url::fromRoute(
                                'entity.taxonomy_term.edit_form',
                                ['taxonomy_term' => $term->id()],
                                [
                                    'query' =>
                                        [
                                            'destination' => Url::fromRoute(
                                                'taxonomy_manager.index',
                                                ['vid' => $vid]
                                            )
                                                ->toString(),
                                        ],
                                ]
                            ),
                        ],
                        'delete' => [
                            'title' => $this->t('Delete'),
                            'url'   => URL::fromRoute(
                                'taxonomy_manager.index.delete'
                            )
                                ->setRouteParameters(
                                    [
                                        'vid' => $vid,
                                        'tid' => $term->id(),
                                    ]
                                ),
                        ],
                    ],
                ],
            ];

            $return[] = $my_array;
        }

        return $return;
    }

    /**
     * @param $tids
     *
     * @return array
     */
    public function getReferencesResults($tids){
        /**
         * @var array $names
         */
        $names = $this->getMultipleTidNames($tids);

        /**
         * @var array $results
         */
        $results = $this->getTablesToUpdate();
        $counter = 0;
        $my_array = [];

        foreach($results as $key => $result){

            foreach($names as $tid => $name){

                /**
                 * @var array $occurrences
                 */
                $occurrences = $this->getOccurrences($key, $result, $tid);
                $occurrences = count($occurrences);

                if($occurrences > 0){
                    $my_array[$counter] = [
                        'tablename' => $key,
                        'tag_name' => $name,
                        'occurrences' => $occurrences
                    ];
                }

                $counter++;
            }
        }

        return $my_array;
    }

    /**
     * @param $field
     * @param $tid
     *
     * @return string
     */
    public function getOccurrences($table, $field, $tid){

        $query_selectReferences = \Drupal::database()->select($table, 'tbl');
        $query_selectReferences->condition($field, $tid);
        $query_selectReferences->fields('tbl');
        $return = $query_selectReferences->execute();

        return $return->fetchAll();
    }

    /**
     * @return array
     * @throws \Drupal\Core\Database\InvalidQueryException
     */
    public function getTablesToUpdate()
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
     * @param $tids
     *
     * @return array
     */
    public function getMultipleTidNames($tids)
    {
        /** @var array $terms */
        $terms = [];

        /** @var array $elementArray */
        $elementArray = [];

        foreach ($tids as $tid) {
            $terms[] = Term::load($tid);
        }

        /**
         * Fill elementArray[tid] = name
         *
         * @var  $term
         */
        foreach ($terms as $term) {
            if ($term != null) {
                $name              = $term->getName();
                $id                = $term->id();
                $elementArray[$id] = $name;
            }
        }

        return $elementArray;
    }

    /**
     * Returns the required new name from selected tid
     *
     * @param $tid
     * @return mixed|null|string
     */
    public function getSingleTidName($tid)
    {
        /** @var  $term */
        $term = Term::load($tid);
        /** @var  $name */
        $name = $term->getName();

        return $name;
    }

    /**
     * @return array
     */
    public function getVids()
    {
        /** @var  $return */
        $return = [];

        /** @var  $query_all */
        $query_all = \Drupal::entityQuery('taxonomy_vocabulary');

        /** @var  $vids */
        $vids = $query_all->execute();

        /** @var  $row */
        foreach ($vids as $row) {
            $vocabulary = Vocabulary::load($row);
            if (null !== $vocabulary) {
                $user = \Drupal::currentUser();
                if ($user->hasPermission(
                    'manage terms in ' . $vocabulary->id()
                )
                ) {
                    $return[$vocabulary->id()] = $vocabulary->id();
                }
            }
        }

        return $return;
    }

    /**
     * @param $form
     * @param $form_state
     *
     * @return array
     */
    public function getSelectedTids($form, $form_state)
    {
        /** @var array $vereinenArray */
        $vereinenArray = $form['table']['#value'];

        /** @var array $tidArray */
        $uebergabeArray = [];

        /** @var $i */
        $i = 1;

        /** Add values to tidArray */
        foreach ($vereinenArray as $key => $vereinenValue) {

            /** @var $valTid */
            $valTid                  = 'tid' . $i;
            $uebergabeArray[$valTid] = $vereinenValue;
            $i++;
        }

        $uebergabeArray['vid'] = $form_state->getValue('vid');

        return $uebergabeArray;
    }

    /**
     * @param $tid
     *
     * @return mixed
     */
    public function deleteTidsFieldData($tid)
    {
        /** @var Term $term */
        $term = Term::load($tid);
        $name = $term->getName();
        $term->delete();

        return $name;
    }

    /**
     * @param $tid
     */
    public function deleteTidFromDB($tid)
    {
        /** @var $references */
        $references = $this->getTablesToUpdate();

        foreach ($references as $table => $field) {
            $this->deleteTidFromReferenceTable($table, $field, $tid);
        }
    }

    /**
     * @param $tableName
     * @param $fieldName
     * @param $tid
     */
    public function deleteTidFromReferenceTable($tableName, $fieldName, $tid)
    {
        /** @var $query_delete_tid */
        $query_delete_tid = \Drupal::database()->delete($tableName);
        $query_delete_tid->condition($fieldName, $tid);
        $query_delete_tid->execute();
    }
}

