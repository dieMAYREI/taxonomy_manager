<?php

namespace Drupal\taxonomy_manager\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * The start form of the taxonomy manager.
 * Build the searchfield, the buttons and the table with the values.
 *
 * Class TaxonomyManagerForm
 *
 * @package Drupal\taxonomy_manager\Form
 */
class TaxonomyManagerForm extends AbstractTaxonomyManagerForm
{

    /** @var array $header */
    private $header;

    /** @var array $results */
    private $results;

    /** @var $arrayVids */
    private $arrayVids;

    /** @var $total */
    private $total;

    /**
     * @return string
     */
    public function getFormId()
    {
        return 'taxonomy_manager_form';
    }

    /**
     * @param array                                $form
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *
     * @return array
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {

        $this->header = [];

        $this->arrayVids = $this->service->getVids();

        /** Set value of sort */
        $form_state->setValue('sort', 'asc');
        if (null !== $this->getRequest()->get('sort')) {
            $form_state->setValue('sort', $this->getRequest()->get('sort'));
        }

        /** Set value of order */
        $form_state->setValue('order', 'tid');
        if (null !== $this->getRequest()->get('order')) {
            $form_state->setValue('order', $this->getRequest()->get('order'));
        }

        /** Set variable page if request isset */
        $form_state->setValue('page', 0);
        if (null !== $this->getRequest()->get('page')) {
            $form_state->setValue('page', $this->getRequest()->get('page'));
        }

        /** Set variable searchValue if request issets */
        $form_state->setValue('search', '');
        if (null !== $this->getRequest()->get('search')) {
            $form_state->setValue('search', $this->getRequest()->get('search'));
        }

        /** Set variable vid if request isset */
        $form_state->setValue('vid', '');
        if (null !== $this->getRequest()->get('vid')) {
            $form_state->setValue('vid', $this->getRequest()->get('vid'));
        }

        /** Set form action */
        $form['#action'] = Url::fromRoute('taxonomy_manager.form')
                ->toString() . '?page=' . $form_state->getValue('page');

        /** Selectfield with the vocabulary (vid) */
        $form['vid'] = [
            '#type'          => 'select',
            '#title'         => $this->t('Vocabulary:'),
            '#options'       => $this->arrayVids,
            '#default_value' => $form_state->getValue('vid'),
            '#attributes'    => [
                'onchange' =>
                    'document.getElementById("edit-search").value = "";
                 this.form.submit();',
            ],
        ];

        /** Fieldset filter */
        $form['filter'] = [
            '#type'  => 'fieldset',
            '#title' => $this->t('search'),
        ];

        /** Searchfield */
        $form['filter']['search'] = [
            '#type'        => 'search',
            '#value'       => $form_state->getValue('search'),
            '#description' => t(
                'Hier können mehrere Begriffe gleichzeitig eingegeben werden. Es werden alle Vorkommnisse der Begriffe gesucht.<br> Beachten Sie bitte, dass bei der Suche nach mehreren Begriffen jeder Begriff in Anführungszeichen steht und <br> jeweils kommagetrennt ist. Beispiel: "begriff 1", "begriff 2"'
            ),
        ];

        /** Sortfield */
        $form['filter']['sort'] = [
            '#type'  => 'hidden',
            '#value' => $form_state->getValue('sort'),
        ];

        /** Orderfield */
        $form['filter']['order'] = [
            '#type'  => 'hidden',
            '#value' => $form_state->getValue('order'),
        ];

        /** SubmitButton Search */
        $form['filter']['searchButton'] = [
            '#type'        => 'submit',
            '#value'       => $this->t('Filter'),
            '#button_type' => 'primary',
            '#attributes'  => ['style' => 'margin-top: 8px;'],
        ];

        /** Fieldset mass_operations */
        $form['mass_operations'] = [
            '#type'  => 'fieldset',
            '#title' => $this->t('Apply to selected items'),
        ];

        /** DeleteButton */
        $form['mass_operations']['delete'] = [
            '#type'        => 'submit',
            '#value'       => $this->t('Delete'),
            '#button_type' => 'primary',
            '#submit'      => ['::deleteSubmitHandler'],
        ];

        /** CombineButton */
        $form['mass_operations']['vereinen'] = [
            '#type'        => 'submit',
            '#value'       => $this->t('Merge'),
            '#button_type' => 'primary',
            '#submit'      => ['::mergeSubmitHandler'],
            '#attributes'  => ['style' => 'margin-top: 8px;'],
        ];

        /** Table header */
        $this->header = [

            'tid' => [
                'data'      => t('tid'),
                'field'     => 'tid',
                'sort'      => $form_state->getValue('sort'),
                'specifier' => 'tid',
            ],

            'name'        => [
                'data'      => 'name',
                'field'     => 'name',
                'sort'      => $form_state->getValue('sort'),
                'specifier' => 'name',
            ],

            //'name' => t('name'),
            'langcode'    => t('langcode'),
            'vid'         => t('vocabulary'),
            'operationen' => t('operations'),
        ];

        /**
         * Set options of table with the results from function getResults
         */

        $this->results = $this->service->getResults(
            $form_state->getValue('search'), $form_state->getValue('vid')
        );

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
            '#type'       => 'tableselect',
            '#header'     => $this->header,
            '#options'    => $options,
            '#empty'      => t('no results found!'),
            '#attributes' => ['style' => 'margin-top: 12px;'],
        ];

        /** Pager */
        $form['pager'] = [
            '#type'       => 'pager',
            '#quantity'   => '10',
            '#result'     => $options,
            '#parameters' => [
                'search' => $form_state->getValue('search'),
                'vid'    => $form_state->getValue('vid'),
            ],
        ];
        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        parent::validateForm(
            $form, $form_state
        ); // TODO: Change the autogenerated stub

        if(!in_array($form_state->getValue('vid'), taxonomy_vocabulary_get_names())){
            $form_state->setErrorByName('vid', $this->t('The Vocabulary does not exist!'));
        }
    }

    /**
     * @param array              $form
     * @param FormStateInterface $form_state
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        /** Rebuild the page after submit(search) with requested values vid, search and page */

        /** @var  $redirectArray */
        $redirectArray = [];

        if ($form_state->getValue('search') !== '') {
            /** @noinspection ReferenceMismatchInspection */
            $redirectArray['search'] = $form_state->getValue('search');
        }

        /** @noinspection ReferenceMismatchInspection */
        $redirectArray['vid'] = $form_state->getValue('vid');

        /** @var $options */
        $options = [];

        $form_state->setRedirect(
            'taxonomy_manager.form', $redirectArray, $options
        );
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
     * @param array                                $form
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     */
    public function mergeSubmitHandler(array &$form, FormStateInterface $form_state)
    {
        $uebergabeArray = $this->getSelectedTids($form, $form_state);

        $form_state->setRedirect(
            'taxonomy_manager.merge.form', $uebergabeArray,
            $options = []
        );
    }

    /**
     * @param array                                $form
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     */
    public function deleteSubmitHandler(array &$form, FormStateInterface $form_state
    ) {
        $uebergabeArray = $this->getSelectedTids($form, $form_state);

        $form_state->setRedirect(
            'taxonomy_manager.multidelete.form', $uebergabeArray,
            $options = []
        );
    }
}