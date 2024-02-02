<?php

namespace Drupal\taxonomy_manager\Form;

use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Drupal\Core\Entity\Sql\TableMappingInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Deletes the selected term with requested tid.
 *
 * Class TaxonomyManagerMultiDeleteForm
 *
 * @package Drupal\taxonomy_manager\Form
 */
class TaxonomyManagerMergeFormConfirm extends TaxonomyManagerAbstractForm {

    protected $vid;

    protected $tids;

    protected $newName;

    protected $selectedName;

    /**
     * @return string
     */
    public function getFormId() {
        return 'taxonomy_manager.mergeFormConfirm';
    }

    /**
     * @param array $form
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *
     * @return array
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $this->tids         = $this->getRequest()->get('tids');
        $this->newName      = $this->getRequest()->get('newName');
        $this->selectedName = $this->getRequest()->get('selectedName');

        $term_names  = $this->service->getMultipleTidNames($this->tids);

        if(in_array($this->newName, $term_names)){
            unset($term_names[$this->selectedName]);
        }

        if (count($term_names) > 1) {
            $term_names_string = '';

            foreach ($term_names as $term_name) {
                if(empty($term_names_string)){
                    $term_names_string = $term_names_string . '"' . $term_name . '"';
                }else{
                    $term_names_string = $term_names_string . ', "' . $term_name . '"';
                }
            }

            $form['label'] = [
                '#type'        => 'item',
                '#description' => 'Die Terme ' . $term_names_string . ' werden durch "' . $this->newName . '" ersetzt! <br><br> In folgenden Feldern werden Aktualisierungen durchgeführt:',
            ];
        }
        else {
            $form['label'] = [
                '#type'        => 'item',
                '#description' => 'Der Term "' . reset($term_names) . '" wird durch "' . $this->newName . '" ersetzt! <br><br> In folgenden Feldern werden Aktualisierungen durchgeführt:',
            ];
        }

        $rows = $this->service->getReferencesResults($this->tids);

        /** Table header */
        $header = [

            'tablename' => t('tablename'),

            'tag_name' => [
                'data'      => t('tag name'),
                'field'     => $form_state->getValue('order'),
                'sort'      => $form_state->getValue('sort'),
                'specifier' => 'tag_name',
            ],

            'occurrences' => t('occurrences'),
        ];

        /** Table */
        $form['table'] = [
            '#type'       => 'table',
            '#header'     => $header,
            '#rows'       => $rows,
            '#empty'      => t('no results found!'),
            '#attributes' => ['style' => 'margin-top: 12px;'],
        ];

        /** AbbrechenButton */
        $form['Abbrechen'] = [
            '#type'        => 'submit',
            '#value'       => $this->t('Cancel'),
            '#button_type' => 'primary',
            '#submit'      => ['::abbrechenSubmitHandler'],
        ];

        /** SubmitButton */
        $form['submit'] = [
            '#type'        => 'submit',
            '#value'       => $this->t('Confirm'),
            '#button_type' => 'primary',
        ];

        return $form;
    }

    /**
     * @param array $form
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $vid = [
            'vid' => $this->getRequest()->get('vid'),
        ];

        $options = [];

        $this->merge_service->mergeTerms(
            $this->tids,
            $this->newName,
            $this->selectedName
        );

        $form_state->setRedirect('taxonomy_manager.index', $vid, $options);
    }
}