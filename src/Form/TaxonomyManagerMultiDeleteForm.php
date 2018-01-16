<?php

namespace Drupal\taxonomy_manager\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Deletes the selected term with requested tid.
 *
 * Class TaxonomyManagerMultiDeleteForm
 *
 * @package Drupal\taxonomy_manager\Form
 */
class TaxonomyManagerMultiDeleteForm extends TaxonomyManagerAbstractForm
{

    /** @var $vid */
    private $vid;

    protected $tidsReq;

    protected $newName;

    protected $selectedName;

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'taxonomy_manager.multideleteForm';
    }

    /**
     * @param array              $form
     * @param FormStateInterface $form_state
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $this->tids = [];
        $counter = 1;

        while($this->getRequest()->get('tid'.$counter) != null){
            $this->tidsReq[] = $this->getRequest()->get('tid'.$counter);
            $counter++;
        };

        $rows = $this->service->getReferencesResults($this->tidsReq);

        if (null !== $this->getRequest()->get('vid')) {
            $this->vid = $this->getRequest()->get('vid');
        }

        $header = [

            'tablename' => t( 'tablename' ),

            'tag_name' => [
                'data'      => t( 'tag name' ),
                'field'     => $form_state->getValue( 'order' ),
                'sort'      => $form_state->getValue( 'sort' ),
                'specifier' => 'tag_name',
            ],

            'occurrences'    => t( 'occurrences' ),
        ];

        /** @var $arraySelect */
        $arraySelect = [];

        /** @var $array_elements */
        $array_elements = $this->service->getMultipleTidNames(
            $this->getRequest()->query
        );

        /** @var $termlist */
        $termlist = '<ul>';

        /* Adds arraySelect the value with key in () */
        foreach ($array_elements as $key => $value) {

            if ($key !== '') {

                $this->tids[]      = $key;
                $arraySelect[$key] = $value . ' ( tid: ' . $key . ' )';

                /** @var Term $term */
                $term = Term::load($key);

                $name = '';

                if($term !== NULL) {
                    /** @var string $name */
                    $name = $term->get('name')->getString();
                }

                $termlist .= '<li>' . $name . ' (' . $key . ')</li>';

            }
        }
        $termlist .= '</ul>';

        /**
         * Set form values
         */

        /** Table */
        $form['table'] = [
            '#type'       => 'table',
            '#header'     => $header,
            '#rows'    => $rows,
            '#empty'      => t( 'no results found!' ),
            '#attributes' => [ 'style' => 'margin-top: 12px;' ],
        ];

        /** Text output */
        $form['reallyDelete'] = [
            '#type'  => 'item',
            '#title' => $this->t('This action cannot be undone.'),
        ];

        /** AbbrechenButton */
        $form['Abbrechen'] = [
            '#type'        => 'submit',
            '#value'       => $this->t('Cancel'),
            '#button_type' => 'primary',
            '#submit'      => ['::cancelSubmitHandler'],
        ];

        /** SubmitButton */
        $form['submit'] = [
            '#type'        => 'submit',
            '#value'       => $this->t('Delete'),
            '#button_type' => 'primary',
        ];

        return $form;
    }

    /**
     * Deletes the term
     *
     * @param array              $form
     * @param FormStateInterface $form_state
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {

        $namen = [];

        foreach ($this->tids as $param) {
            $this->service->deleteTidFromDB($param);
            $namen[] = $this->service->deleteTidsFieldData($param) . ' ('
                . $param . ')';
        }

        /** Set redirect to taxonomy_manager_form with the actual vocabulary (vid) */
        /** @var $vid */
        $vid = [
            'vid' => $this->vid,
        ];

        /** @var $options */
        $options = [];

        drupal_set_message(
            t('%name has been deleted.', ['%name' => implode(', ', $namen)]),
            'status', true
        );

        $form_state->setRedirect('taxonomy_manager.index', $vid, $options);
    }

    /**
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     */
    public function cancelSubmitHandler(array &$form, FormStateInterface $form_state)
    {
        /** Set redirect to taxonomy_manager_form */
        /** @var $vid */
        $vid = [
            'vid' => $this->vid,
        ];

        /** @var $options */
        $options = [];

        $form_state->setRedirect('taxonomy_manager.index', $vid, $options);
    }
}