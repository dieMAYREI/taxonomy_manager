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

        if (null !== $this->getRequest()->get('vid')) {
            $this->vid = $this->getRequest()->get('vid');
        }

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

        /** tid textfield */
        $form['termlist'] = [
            '#markup' => $termlist,
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

        $form_state->setRedirect('taxonomy_manager.form', $vid, $options);
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

        $form_state->setRedirect('taxonomy_manager.form', $vid, $options);
    }
}