<?php

namespace Drupal\advanced_taxonomy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * The start form of the taxonomy manager.
 * Build the searchfield, the buttons and the table with the values.
 *
 * Class AdvancedTaxonomyForm
 *
 * @package Drupal\advanced_taxonomy\Form
 */
class AdvancedTaxonomyForm extends FormBase
{

    /** @var integer $results_pro_page */
    private $results_pro_page = 10;

    /** @var integer $results_total */
    private $results_total;

    /** @var array $header */
    private $header;

    /** @var array $results */
    private $results;

    /** @var $arrayVids */
    private $arrayVids;

    /** @var $total */
    private $total;

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'advanced_taxonomy_form';
    }

    /**
     * @param array $form
     * @param FormStateInterface $form_state
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {

        $this->header = [];

        $this->arrayVids = $this->getVids();

        /** Set value of sort */
        $form_state->setValue('sort', 'asc');
        if (isset($_REQUEST['sort'])) {
            $form_state->setValue('sort', $_REQUEST['sort']);
        }

        /** Set value of order */
        $form_state->setValue('order', 'tid');
        if (isset($_REQUEST['order'])) {
            $form_state->setValue('order', $_REQUEST['order']);
        }

        /** Set variable page if request isset */
        $form_state->setValue('page', 0);
        if (isset($_REQUEST['page'])) {
            $form_state->setValue('page', $_REQUEST['page']);
        }

        /** Set variable sucheValue if request isset */
        $form_state->setValue('suche', '');
        if (isset($_REQUEST['suche'])) {
            $form_state->setValue('suche', $_REQUEST['suche']);
        }

        /** Set variable vid if request isset */
        $form_state->setValue('vid', '');
        if (isset($_REQUEST['vid'])) {
            $form_state->setValue('vid', $_REQUEST['vid']);
        }

        /**
         * Set form values
         */

        /** Set form action */
        $form['#action'] = Url::fromRoute('advanced_taxonomy.form')
                ->toString() . "?page=" . $form_state->getValue('page');

        /** Selectfield with the vocabulary (vid) */
        $form['vid'] = [
            '#type' => 'select',
            '#title' => $this->t('Vocabulary:'),
            '#options' => $this->arrayVids,
            '#default_value' => $form_state->getValue('vid'),
            '#attributes' => ['onchange' =>
                'document.getElementById("edit-suche").value = "";
                 this.form.submit();'
            ],
        ];

        /** Fieldset filter */
        $form['filter'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('search'),
        ];

        /** Searchfield */
        $form['filter']['suche'] = [
            '#type' => 'search',
            '#value' => $form_state->getValue('suche'),
        ];

        /** Sortfield */
        $form['filter']['sort'] = [
            '#type' => 'hidden',
            '#value' => $form_state->getValue('sort'),
        ];

        /** Orderfield */
        $form['filter']['order'] = [
            '#type' => 'hidden',
            '#value' => $form_state->getValue('order'),
        ];

        /** SubmitButton Search */
        $form['filter']['sucheButton'] = [
            '#type' => 'submit',
            '#value' => $this->t('Filter'),
            '#button_type' => 'primary',
            '#attributes' => ['style' => 'margin-top: 8px;'],
        ];

        /** Fieldset mass_operations */
        $form['mass_operations'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Apply to selected items'),
        ];

        /** DeleteButton */
        $form['mass_operations']['delete'] = [
            '#type' => 'submit',
            '#value' => $this->t('Delete'),
            '#button_type' => 'primary',
            '#submit' => ['::table_form_delete'],
        ];

        /** CombineButton */
        $form['mass_operations']['vereinen'] = [
            '#type' => 'submit',
            '#value' => $this->t('Merge'),
            '#button_type' => 'primary',
            '#submit' => ['::table_form_combine'],
            '#attributes' => ['style' => 'margin-top: 8px;'],
        ];

        /** Table header */
        $this->header = [

            'tid' => [
                'data' => t('tid'),
                'field' => $form_state->getValue('order'),
                'sort' => $form_state->getValue('sort'),
                'specifier' => 'tid',
            ],

            'name' => t('name'),
            'langcode' => t('langcode'),
            'vid' => t('vocabulary'),
            'operationen' => t('operations'),

        ];

        /**
         * Set options of table with the results from function getResults
         */

        $this->results = $this->getResults($form_state->getValue('suche'), $form_state->getValue('vid'));

        /**
         * Set tid as key foreach result
         *
         * @var $options
         */
        $options = [];
        foreach ($this->results as $result) {
            $options[$result['tid']] = $result;
        }

        /**
         * Build table and pagination
         */

        /** Table */
        $form['table'] = [
            '#type' => 'tableselect',
            '#header' => $this->header,
            '#options' => $options,
            '#empty' => t('no results found!!!'),
            '#attributes' => ['style' => 'margin-top: 12px;'],
        ];

        /** Pager */
        $form['pager'] = [
            '#type' => 'pager',
            '#quantity' => '10',
            '#result' => $options,
            '#parameters' => [
                'suche' => $form_state->getValue('suche'),
                'vid' => $form_state->getValue('vid'),
            ],
        ];

        /** Text output */
        $form['terms'] = [
            '#type' => 'item',
            '#title' => $this->t('Terms: ' . $this->total),
        ];

        return $form;

    }

    /**
     * @param array $form
     * @param FormStateInterface $form_state
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {

    }

    /**
     * @param array $form
     * @param FormStateInterface $form_state
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {

        /** Rebuild the page after submit(search) with requested values vid, search and page */

        /** @var  $redirectArray */
        $redirectArray = [];

        if ($form_state->getValue('suche') != "") {

            $redirectArray['suche'] = $form_state->getValue('suche');

        }

        $redirectArray['vid'] = $form_state->getValue('vid');

        /** @var $options */
        $options = [];

        $form_state->setRedirect('advanced_taxonomy.form', $redirectArray, $options);

    }

    /**
     * Get terms from database
     *
     * @param $values
     *
     * @return array
     */
    public function getResults($values, $vid)
    {

        /** @var $return */
        $return = [];

        /** @var $vids */
        $vids = $this->getVids();

        /** @var $suchValues */
        $suchValues = trim($values);

        /** @var $suchArray */
        if ($suchValues) {
            $suchArray = explode(' ', $suchValues);
        }

        /** @var  $query_all */
        $query_all = \Drupal::entityQuery('taxonomy_term');

        /** Sort rows by header (tid) */
        $query_all->tableSort($this->header);

        /** Query_all like the vid, if vid is not empty */
        if ($vid != "") {

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
                $group->condition('name', '%' . \Drupal::database()
                        ->escapeLike($searchString) . '%', 'LIKE');
                $group->condition('tid', '%' . \Drupal::database()
                        ->escapeLike($searchString) . '%', 'LIKE');
                $group->condition('vid', '%' . \Drupal::database()
                        ->escapeLike($searchString) . '%', 'LIKE');
                $group->condition('langcode', '%' . \Drupal::database()
                        ->escapeLike($searchString) . '%', 'LIKE');
            }

            $query_all->condition($group);

        }

        /** @var $count */
        $count = $query_all->execute();

        /** @var $totalCountTerms */
        $totalCountTerms = count($count);

        /** @var $queryTotal */
        $queryTotal = $query_all;

        /** @var $resultTotal */
        $resultTotal = $queryTotal->execute();

        /** @var $totalTermsDisplay */
        $totalTermsDisplay = count($resultTotal);
        $this->results_total = $totalTermsDisplay;

        /** @var $page */
        $page = pager_default_initialize($this->results_total, $this->results_pro_page);

        /** @var $offset */
        $offset = $this->results_pro_page * $page;

        /** @var $results */
        $results = $query_all->range($offset, $this->results_pro_page)->execute();

        /**
         * Shows the number of actual paged terms from total terms
         *
         * @var  $end
         */
        $end = $offset + $this->results_pro_page;

        /** @var $begin */
        if ($totalCountTerms == 0) {

            $begin = $offset;

        } else {

            $begin = $offset + 1;

        }

        if ($end < $totalCountTerms) {

            $this->total = $begin . ' - ' . $end . ' / ' . $totalCountTerms;

        } else {

            $this->total = $begin . ' - ' . $totalCountTerms . ' / ' . $totalCountTerms;

        }

        /**
         * Build rows
         */

        foreach ($results as $row) {

            /** @var $term */
            $term = Term::load($row);

            /** @var $my_array */
            $my_array = [];
            $my_array['tid'] = $term->id();
            $my_array['name'] = $term->getName();
            $my_array['langcode'] = $term->get('langcode')->getLangcode();
            $my_array['vid'] = $term->getVocabularyId();

            $my_array['operationen'] = [
                'data' => [
                    '#type' => 'dropbutton',
                    '#links' => [
                        'edit' => [
                            'title' => $this->t('Bearbeiten'),
                            'url' => Url::fromRoute(
                                'entity.taxonomy_term.edit_form',
                                ['taxonomy_term' => $term->id()],
                                [
                                    'query' =>
                                        [
                                            'destination' => '/admin/advanced_taxonomy-form?vid=' . $vid,
                                        ],
                                ]
                            ),
                        ],
                        'delete' => [
                            'title' => $this->t('Delete'),
                            'url' => Url::fromUri('internal:/admin/advanced_taxonomy-loeschen/' . $vid . '/'
                                . $term->id()),
                        ],
                    ],
                ],
            ];

            $return[] = $my_array;

        }

        return $return;

    }

    /**
     * Delete tid from table taxonomy_term_field_data
     *
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function table_form_delete(array &$form, FormStateInterface $form_state)
    {

        $deleteArray = $form['table']['#value'];

        foreach ($deleteArray as $deleteValue) {

            $this->updateDrupalDatabase($deleteValue);

            /** @var  $term */
            $term = Term::load($deleteValue);
            $term->delete();

        }

        /** @var $vid */
        $vid = [
            'vid' => $form_state->getValue('vid'),
        ];

        /** @var  $options */
        $options = [];

        $form_state->setRedirect('advanced_taxonomy.form', $vid, $options);

    }

    /**
     * Combines two selected tids
     *
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function table_form_combine(array &$form, FormStateInterface $form_state)
    {

        /** @var $vereinenArray */
        $vereinenArray = $form['table']['#value'];

        /** @var $tidArray */
        $uebergabeArray = [];

        /** @var  $i */
        $i = 1;

        /** Add values to tidArray */
        foreach ($vereinenArray as $key => $vereinenValue) {

            /** @var  $valTid */
            $valTid = 'tid' . $i;
            $uebergabeArray[$valTid] .= $vereinenValue;
            $i++;

        }

        $uebergabeArray['vid'] = $form_state->getValue('vid');

        /** @var $options */
        $options = array();

        $form_state->setRedirect('advanced_taxonomy_Vereinen.form', $uebergabeArray, $options);

    }

    /**
     * Get the vids from database
     *
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
            $return[$vocabulary->id()] = $vocabulary->id();
        }

        return $return;

    }

    /**
     * Updates the database with the new tid and deletes the old tid
     *
     * @param $tid
     */
    public function updateDrupalDatabase($tid)
    {

        /** @var $references */
        $references = $this->getReferences();

        /**
         * Get tablename and fieldname of the references
         * Update database with values
         *
         * @var $row
         */
        while ($row = $references->fetchAssoc()) {

            /** @var $data */
            $data = unserialize($row['data']);

            if (isset($data['field_type'])) {

                if ($data['field_type'] == 'entity_reference') {

                    /** @var $tableName */
                    $tableName = $data['entity_type'] . '__' . $data['field_name'];

                    /** @var $fieldName */
                    $fieldName = $data['field_name'] . '_target_id';

                    $this->deleteTidFromReferenceTable($tableName, $fieldName, $tid);

                }

            }

        }

    }

    /**
     * Return the required entity references
     *
     * @return \Drupal\Core\Database\StatementInterface|null
     */
    public function getReferences()
    {

        /** @var $query_references */
        $query_references = \Drupal::database()->select('config', 'data');
        $query_references->condition('data', "%" . $query_references->escapeLike('entity_reference') . "%", 'LIKE');
        $query_references->fields('data', ['data']);

        /** @var $references */
        $references = $query_references->execute();

        return $references;

    }

    /**
     * Delete tid from the reference tables
     *
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