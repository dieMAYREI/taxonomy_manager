<?php

namespace Drupal\taxonomy_manager\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Deletes the selected term with requested tid.
 *
 * Class TaxonomyManagerDeleteForm
 *
 * @package Drupal\taxonomy_manager\Form
 */
class TaxonomyManagerDeleteForm extends TaxonomyManagerAbstractForm
{

    /** @var $vid */
    private $vid;

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'taxonomy_manager.deleteForm';
    }

    /**
     * @param array              $form
     * @param FormStateInterface $form_state
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        /** @var  $route_match */
        $route_match = \Drupal::service('current_route_match');

        /** @var  $tid */
        $tid = $route_match->getParameter('tid');

        $this->vid = $route_match->getParameter('vid');

        $vid      = '';
        $name     = '';
        $langcode = '';

        /** get other values */
        if (!$form_state->getValue('form_id')) {

            /** @var Term $term */
            $term = Term::load($tid);

            /** @var $vid */
            if (null !== $term) {
                $vid = $term->get('vid')->getString();
            }

            /** @var $name */
            $name = $term->get('name')->getString();

            /** @var $langcode */
            $langcode = $term->get('langcode')->getString();
        }

        /**
         * Set form values
         */

        /** tid textfield */
        $form['tid'] = [
            '#type'     => 'textfield',
            '#title'    => t('tid:'),
            '#value'    => $tid,
            '#disabled' => true,
        ];

        /** name textfield */
        $form['name'] = [
            '#type'     => 'textfield',
            '#title'    => t('name:'),
            '#value'    => $name,
            '#disabled' => true,
        ];

        /** vid textfield */
        $form['vid'] = [
            '#type'     => 'textfield',
            '#title'    => t('vid:'),
            '#value'    => $vid,
            '#disabled' => true,
        ];

        /** langcode textfield */
        $form['langcode'] = [
            '#type'     => 'textfield',
            '#title'    => t('langcode:'),
            '#value'    => $langcode,
            '#disabled' => true,
        ];

        /** Text output */
        $form['reallyDelete'] = [
            '#type'  => 'item',
            '#title' => $this->t('Really Delete?'),
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
        $this->service->deleteTidFromDB($form_state->getValue('tid'));

        /** @var Term $term */
        $term = Term::load($form_state->getValue('tid'));

        $name = '';
        if ($term !== null) {
            $name = $term->getName();
            $term->delete();
        }

        /** Set redirect to taxonomy_manager_form with the actual vocabulary (vid) */
        /** @var $vid */
        $vid = [
            'vid' => $this->vid,
        ];

        /** @var $options */
        $options = [];

        drupal_set_message($name . ' deleted!', 'status', true);

        $form_state->setRedirect('taxonomy_manager.form', $vid, $options);
    }


    /**
     * @param array                                $form
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        parent::validateForm(
            $form, $form_state
        );

        if (!is_numeric($form_state->getValue('tid'))) {
            $form_state->setErrorByName(
                'tid', $this->t('TID is expected to be an integer')
            );
        }

        if (empty(Term::load($form_state->getValue('tid')))) {
            $form_state->setErrorByName(
                'tid', $this->t('Term does not exist!')
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

        $form_state->setRedirect('taxonomy_manager.form', $vid, $options);
    }
}