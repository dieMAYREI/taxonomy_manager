<?php
namespace Drupal\advanced_taxonomy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Deletes the selected term with requested tid.
 *
 * Class AdvancedTaxonomyLoeschenForm
 * @package Drupal\advanced_taxonomy\Form
 */
class AdvancedTaxonomyLoeschenForm extends FormBase
{
    /** @var $vid */
    private $vid;

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'advanced_taxonomy_loeschenForm';
    }

    /**
     * @param array $form
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

        /** get other values */
        if (!$form_state->getValue('form_id')) {

            /** @var $term */
            $term = Term::load($tid);

            /** @var $vid */
            $vid = $term->get('vid')->getString();

            /** @var $name */
            $name = $term->get('name')->getString();

            /** @var $langcode */
            $langcode = $term->get('langcode')->getString();

        }

        /**
         * Set form values
         */

        /** tid textfield */
        $form['tid'] = array(
            '#type' => 'textfield',
            '#title' => t('tid:'),
            '#value' => $tid,
            '#disabled' => true,
        );

        /** name textfield */
        $form['name'] = array(
            '#type' => 'textfield',
            '#title' => t('name:'),
            '#value' => $name,
            '#disabled' => true,
        );

        /** vid textfield */
        $form['vid'] = array(
            '#type' => 'textfield',
            '#title' => t('vid:'),
            '#value' => $vid,
            '#disabled' => true,
        );

        /** langcode textfield */
        $form['langcode'] = array(
            '#type' => 'textfield',
            '#title' => t('langcode:'),
            '#value' => $langcode,
            '#disabled' => true,
        );

        /** Text output */
        $form['reallyDelete'] = array(
            '#type' => 'item',
            '#title' => $this->t('Really Delete?'),
        );

        /** AbbrechenButton */
        $form['Abbrechen'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Cancel'),
            '#button_type' => 'primary',
            '#submit' => array('::abbrechenSubmitHandler'),
        );

        /** SubmitButton */
        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Delete'),
            '#button_type' => 'primary',
        );

        return $form;

    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {

    }

    /**
     * Deletes the term
     *
     * @param array $form
     * @param FormStateInterface $form_state
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->updateDrupalDatabase($form_state->getValue('tid'));

        /** @var  $term */
        $term = Term::load($form_state->getValue('tid'));
        $name = $term->getName();
        $term->delete();

        /** Set redirect to advanced_taxonomy_form with the actual vocabulary (vid) */
        /** @var $vid */
        $vid = array(
            'vid' => $this->vid,
        );

        /** @var $options */
        $options = array();

        drupal_set_message($name . ' deleted!', 'status', TRUE);

        $form_state->setRedirect('advanced_taxonomy.form', $vid, $options);

    }

    /**
     * Redirects to AdvancedTaxonomyForm
     *
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function abbrechenSubmitHandler(array &$form, FormStateInterface $form_state)
    {

        /** Set redirect to advanced_taxonomy_form */
        /** @var $vid */
        $vid = array(
            'vid' => $this->vid,
        );

        /** @var $options */
        $options = array();

        $form_state->setRedirect('advanced_taxonomy.form', $vid, $options);

    }

    /**
     * Updates the database with the new tid and deletes the old term
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
        $query_references->fields('data', array('data'));

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