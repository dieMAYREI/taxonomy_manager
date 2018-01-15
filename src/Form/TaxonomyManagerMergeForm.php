<?php

namespace Drupal\taxonomy_manager\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;

/**
 *  Combines two selected terms to one.
 *
 * Class TaxonomyManagerMergeForm
 *
 * @package Drupal\taxonomy_manager\Form
 */
class TaxonomyManagerMergeForm extends TaxonomyManagerAbstractForm
{

    /** @var $vid */
    private $vid;

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'taxonomy_manager.mergeForm';
    }

    /**
     * @param array              $form
     * @param FormStateInterface $form_state
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $vid = $this->getRequest()->get('vid');

        if (null !== $vid) {
            $this->vid = $vid;
        }

        $mergableTerms = $this->merge_service->getMergeableTerms(
            $this->getRequest()->query
        );

        $arraySelect = $mergableTerms[0];
        $this->tids  = $mergableTerms[1];

        /**
         * Set form values
         */

        $form['vid'] = [
            '#type'    => 'hidden',
            '#value' => $vid,
        ];

        /** Selectfield with the tids */
        $form['name_select'] = [
            '#type'    => 'select',
            '#title'   => $this->t('Selected Terms:'),
            '#options' => $arraySelect,
        ];

        /** Textfield with the new name */
        $form['addfield'] = [
            '#type'        => 'textfield',
            '#title'       => t('New Name:'),
            '#description' => t(
                'Wenn dieses Feld leer bleibt, wird der im Klappmenü ausgewählte Term verwendet.<br>
        Ansonsten werden alle Terms auf den hier eingegebenen Namen eingestellt.'
            ),
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
            '#value'       => $this->t('Merge'),
            '#button_type' => 'primary',
        ];

        return $form;
    }

    /**
     * @param array              $form
     * @param FormStateInterface $form_state
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        if ($this->getRequest()->get('addfield') != '') {
            $newName = $this->getRequest()->get('addfield');
        } else {
            $newName = $this->service->getSingleTidName($form_state->getValue('name_select'));
        }

        $options = [
            'tids' => $this->tids,
            'newName' => $newName,
            'selectedName' => (int)$form_state->getValue('name_select')
        ];

        $form_state->setRedirect('taxonomy_manager.index.merge.confirm', $options);
    }

    /**
     * @param array                                $form
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        if ($this->merge_service->checkIfNameAlreadyExists(
            $form_state->getValue('addfield'), $form_state->getValue('vid')
        )
        ) {
            $form_state->setErrorByName(
                'addfield', $this->t('Term with this name already exists!')
            );
        }
    }

    /**
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     */
    public function cancelSubmitHandler(FormStateInterface $form_state)
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