<?php

namespace Drupal\taxonomy_manager\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;

/**
 *  Combines two selected terms to one.
 *
 * Class TaxonomyManagerVereinenForm
 * @package Drupal\taxonomy_manager\Form
 */
class TaxonomyManagerMergeForm extends FormBase {

	/** @var array $tids */
	private $tids = [];

    /** @var $vid */
    private $vid;

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
	    return 'taxonomy_manager_merge_form';
    }

    /**
     * @param array $form
     * @param FormStateInterface $form_state
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {

        if (isset($_REQUEST['vid'])) {
            $this->vid = $_REQUEST['vid'];
        }

        /** @var $arraySelect */
        $arraySelect = array();

        /** @var $array_elements */
        $array_elements = $this->getElements();

        /* Adds arraySelect the value with key in () */
        foreach ($array_elements as $key => $value) {

            if ($key != "") {

                $this->tids[] = $key;
                $arraySelect[$key] = $value . ' ( tid: ' . $key . ' )';

            }

        }

        /**
         * Set form values
         */

        /** Selectfield with the vids */
        $form['name_select'] = [
            '#type' => 'select',
            '#title' => $this->t('Selected Terms:'),
            '#options' => $arraySelect,
        ];

        /** Textfield with the new name */
        $form['addfield'] = array(
            '#type' => 'textfield',
            '#title' => t('New Name: (replaces the name of the selected term and deletes other one!)'),
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
            '#value' => $this->t('Merge'),
            '#button_type' => 'primary',
        );

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

        /** @var $new_tid */
        $new_tid = $form_state->getValue('name_select');

        /**
         * Set new name with value of textfield, if isset
         * Else set name with selected value
         * @var $new_vid
         */
        if ($form_state->getValue('addfield') != "") {
	        $new_name      = $form_state->getValue( ( 'addfield' ) );
	        $resultNewName = $this->checkIfNameAlreadyExists( $new_name );
        } else {
            $new_name = $this->getName($new_tid);
        }

        /*
         * Call functions if new name do not already exists
         */

        if ($resultNewName != -1) {

            $this->updateNames($new_name);
            $this->updateDrupalDatabase($new_tid);
            $this->deleteTidsFieldData($new_tid);

	        /** Set redirect to taxonomy_manager_form */
            /** @var $vid */
            $vid = array(
                'vid' => $this->vid,
            );

            /** @var $options */
            $options = array();

            drupal_set_message('Merge succesfully!', 'status', TRUE);
	        $form_state->setRedirect( 'taxonomy_manager.form', $vid, $options );


        } else {

            /** If name already exists redirect and throw message */
            drupal_set_message(t('Name already exists!'), 'error', TRUE);

            /**
             * Set tids and vid to redirect after name already exists
             * @var  $redirectArray
             */
            $redirectArray = array();
            $redirectArray[] = $this->tids;
            $redirectArray['vid'] = $this->vid;

            $options = array();

	        $form_state->setRedirect( 'taxonomy_manager_Vereinen.form', $redirectArray, $options );

        }

    }

    /**
     * Redirects to TaxonomyManagerForm
     *
     * @param array $form
     * @param FormStateInterface $form_state
     */
	public function cancelSubmitHandler( array &$form, FormStateInterface $form_state )
    {

	    /** Set redirect to taxonomy_manager_form */
        /** @var $vid */
        $vid = array(
            'vid' => $this->vid,
        );

        /** @var $options */
        $options = array();

	    $form_state->setRedirect( 'taxonomy_manager.form', $vid, $options );

    }

    /**
     * Returns the required tids (as keys) and vids (as values) in an array
     *
     * @return array
     */
    public function getElements()
    {

        /** @var  $terms */
        $terms = array();

        /** @var  $elementArray */
        $elementArray = array();

        /**
         * Load terms
         * @var  $param
         */

        foreach ($_REQUEST as $param) {

            $terms[] = Term::load($param);

        }

        /**
         * Fill elementArray[tid] = name
         * @var  $term
         */
        foreach ($terms as $term) {

            if ($term != null) {

                $name = $term->getName();
                $id = $term->id();
                $elementArray[$id] = $name;

            }

        }

        return $elementArray;

    }

    /**
     * Update old names with new
     * @param $name
     */
    public function updateNames($name)
    {
        /** @var  $param */
        foreach ($this->tids as $param) {

            /** @var  $term */
            $term = Term::load($param);
            $term->setName($name);
            $term->save();

        }

    }

    /**
     * Deletes the other tids from the table
     * @param $tid
     */
    public function deleteTidsFieldData($tid)
    {

        /** @var  $param */
        foreach ($this->tids as $param) {
            if ($param != $tid) {
                /** @var  $term */
                $term = Term::load($param);
                $term->delete();

            }
        }

    }

    /**
     * Returns the required new name
     *
     * @param $tid
     * @return mixed|null|string
     */
    public function getName($tid)
    {
        /** @var  $term */
        $term = Term::load($tid);

        /** @var  $name */
        $name = $term->getName();

        return $name;

    }

    /**
     * Updates the database with the new tid and deletes the old tid
     *
     * @param $tid
     */
    public function updateDrupalDatabase($tid)
    {

        /** Clear cache */
        foreach (Cache::getBins() as $service_id => $cache_backend) {
            $cache_backend->deleteAll();
        }

        foreach ($this->tids as $param) {

            /** @var $references */
            $references = $this->getReferences();

            /**
             * Get tablename and fieldname of the references
             * Update database with values
             * @var $row
             */
            while ($row = $references->fetchAssoc()) {

                /** @var $data */
	            $data = unserialize( $row['data'], true );

                if (isset($data['field_type'])) {

                    if ($data['field_type'] == 'entity_reference') {

                        /** @var $tableName */
                        $tableName = $data['entity_type'] . '__' . $data['field_name'];

                        /** @var $fieldName */
                        $fieldName = $data['field_name'] . '_target_id';

                        /**
                         * Get total count of tids in the table
                         * @var $total
                         */
                        $total = $this->getTotalCountReferenceTable($tableName, $fieldName);

                        /** Delete tid from reference table if there is more than one tid */
                        while ($total > 1) {
                            $this->deleteTidFromReferenceTable($tableName, $fieldName, $tid);
                            $total--;
                        }

                        /** Update tid in reference tables */
                        $this->updateTidReferenceTable($tableName, $fieldName, $tid, $param);

                    }

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

        /** @var $references */
	    $references = \Drupal::database()->select( 'config', 'data' );
	    $references->condition( 'data', "%" . $references->escapeLike( 'entity_reference' ) . "%", 'LIKE' );
	    $references->fields( 'data', array( 'data' ) );

	    /** @var $references */
	    $references = $references->execute();

        return $references;

    }

    /**
     * Get the count of tids from the reference tables
     *
     * @param $tableName
     * @param $fieldName
     * @return int
     */
    public function getTotalCountReferenceTable($tableName, $fieldName)
    {

	    /** @var $query */
	    $query = \Drupal::database()->select( $tableName, 'data' );
	    $query->fields( 'data', array( $fieldName ) );
	    $total = $query->execute()->fetchAll();

	    return count( $total );

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

	    /** @var $delete */
	    $delete = \Drupal::database()->delete( $tableName );
	    $delete->condition( $fieldName, $tid );
	    $delete->execute();

    }

    /**
     * Updates the tids in the reference tables
     *
     * @param $tableName
     * @param $fieldName
     * @param $tid
     * @param $oldTid
     */
    public function updateTidReferenceTable($tableName, $fieldName, $tid, $oldTid)
    {
	    /** @var $update */
	    $update = \Drupal::database()->update( $tableName );
	    $update->fields( [ $fieldName => $tid ] );
	    $update->condition( $fieldName, $oldTid );
	    $update->execute();

    }

    /**
     * Returns -1 if the new name already exists
     * @param $newName
     * @return int
     */
	public function checkIfNameAlreadyExists( $newName )
    {

	    /** @var  $query */
	    $query = \Drupal::entityQuery( 'taxonomy_term' );
	    $query->condition( 'name', '%' . \Drupal::database()->escapeLike( $newName ) . '%', 'LIKE' );

        /** @var  $name */
	    $name = $query->execute();

        if ($name != null) {
            return -1;
        }

        return 0;

    }

}