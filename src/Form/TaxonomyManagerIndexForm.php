<?php

namespace Drupal\taxonomy_manager\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;

/**
 * The start form of the taxonomy manager.
 * Build the searchfield, the buttons and the table with the values.
 *
 * Class TaxonomyManagerForm
 *
 * @package Drupal\taxonomy_manager\Form
 */
class TaxonomyManagerIndexForm extends TaxonomyManagerAbstractForm {

	/** @var array $header */
	private $header;

	/** @var array $results */
	private $results;

	/** @var $arrayVids */
	private $arrayVids;

	/**
	 * @return string
	 */
	public function getFormId(){
		return 'taxonomy_manager_form';
	}

	/**
	 * @param array $form
	 * @param \Drupal\Core\Form\FormStateInterface $form_state
	 *
	 * @return array
	 */
	public function buildForm( array $form, FormStateInterface $form_state ){

		$this->header = [];

		$this->arrayVids = $this->service->getVids();

		/** Set value of sort */
		$form_state->setValue( 'sort', 'asc' );
		if ( isset( $_REQUEST['sort'] ) ) {
			$form_state->setValue( 'sort', $_REQUEST['sort'] );
		}

		/** Set value of order */
		$form_state->setValue( 'order', 'tid' );
		if ( isset( $_REQUEST['order'] ) ) {
			$form_state->setValue( 'order', $_REQUEST['order'] );
		}

		/** Set variable page if request isset */
		$form_state->setValue( 'page', 0 );
		if ( isset( $_REQUEST['page'] ) ) {
			$form_state->setValue( 'page', $_REQUEST['page'] );
		}

		/** Set variable sucheValue if request isset */
		$form_state->setValue( 'suche', '' );
		if ( isset( $_REQUEST['suche'] ) ) {
			$form_state->setValue( 'suche', $_REQUEST['suche'] );
		}

		/** Set variable vid if request isset */
		$form_state->setValue( 'vid', '' );
		if ( isset( $_REQUEST['vid'] ) ) {
			$form_state->setValue( 'vid', $_REQUEST['vid'] );
		}

		/** Set form action */
		$form['#action'] = Url::fromRoute( 'taxonomy_manager.form' )
		                      ->toString() . '?page=' . $form_state->getValue( 'page' );

		/** Selectfield with the vocabulary (vid) */
		$form['vid'] = [
			'#type'          => 'select',
			'#title'         => $this->t( 'Vocabulary:' ),
			'#options'       => $this->arrayVids,
			'#default_value' => $form_state->getValue( 'vid' ),
			'#attributes'    => [
				'onchange' =>
					'document.getElementById("edit-suche").value = "";
                 this.form.submit();',
			],
		];

		/** Fieldset filter */
		$form['filter'] = [
			'#type'  => 'fieldset',
			'#title' => $this->t( 'search' ),
		];

		/** Searchfield */
		$form['filter']['suche'] = [
			'#type'  => 'search',
			'#value' => $form_state->getValue( 'suche' ),
		];

		/** Sortfield */
		$form['filter']['sort'] = [
			'#type'  => 'hidden',
			'#value' => $form_state->getValue( 'sort' ),
		];

		/** Orderfield */
		$form['filter']['order'] = [
			'#type'  => 'hidden',
			'#value' => $form_state->getValue( 'order' ),
		];

		/** SubmitButton Search */
		$form['filter']['sucheButton'] = [
			'#type'        => 'submit',
			'#value'       => $this->t( 'Filter' ),
			'#button_type' => 'primary',
			'#attributes'  => [ 'style' => 'margin-top: 8px;' ],
		];

		/** Fieldset mass_operations */
		$form['mass_operations'] = [
			'#type'  => 'fieldset',
			'#title' => $this->t( 'Apply to selected items' ),
		];

		/** DeleteButton */
		$form['mass_operations']['delete'] = [
			'#type'        => 'submit',
			'#value'       => $this->t( 'Delete' ),
			'#button_type' => 'primary',
			'#submit'      => [ '::table_form_delete' ],
		];

		/** CombineButton */
		$form['mass_operations']['vereinen'] = [
			'#type'        => 'submit',
			'#value'       => $this->t( 'Merge' ),
			'#button_type' => 'primary',
			'#submit'      => [ '::table_form_combine' ],
			'#attributes'  => [ 'style' => 'margin-top: 8px;' ],
		];

		/** Table header */
		$this->header = [

			'tid' => t( 'tid' ),

			'name' => [
				'data'      => t( 'name' ),
				'field'     => $form_state->getValue( 'order' ),
				'sort'      => $form_state->getValue( 'sort' ),
				'specifier' => 'name',
			],

			'langcode'    => t( 'langcode' ),
			'vid'         => t( 'vocabulary' ),
			'operationen' => t( 'operations' ),

		];

		/**
		 * Set options of table with the results from function getResults
		 */

		$this->results = $this->service->getResults( $form_state->getValue( 'suche' ),
			$form_state->getValue( 'vid' ) );

		/**
		 * Set tid as key foreach result
		 *
		 * @var $options
		 */
		$options = [];
		foreach ( $this->results as $result ) {
			$options[ $result['tid'] ] = $result;
		}

		/**
		 * Build table and pagination
		 */

		/** Table */
		$form['table'] = [
			'#type'       => 'tableselect',
			'#header'     => $this->header,
			'#options'    => $options,
			'#empty'      => t( 'no results found!!!' ),
			'#attributes' => [ 'style' => 'margin-top: 12px;' ],
		];

		/** Pager */
		$form['pager'] = [
			'#type'       => 'pager',
			'#quantity'   => '10',
			'#result'     => $options,
			'#parameters' => [
				'suche' => $form_state->getValue( 'suche' ),
				'vid'   => $form_state->getValue( 'vid' ),
			],
		];

		return $form;
	}

	/**
	 * @param array $form
	 * @param FormStateInterface $form_state
	 * {@inheritdoc}
	 */
	public function submitForm( array &$form, FormStateInterface $form_state ){

		/** Rebuild the page after submit(search) with requested values vid, search and page */

		/** @var  $redirectArray */
		$redirectArray = [];

		/** @var TYPE_NAME $form_state */
		if ( $form_state->getValue( 'suche' ) != "" ) {
			$redirectArray['suche'] = $form_state->getValue( 'suche' );
		}

		$redirectArray['vid'] = $form_state->getValue( 'vid' );

		/** @var $options */
		$options = [];

		$form_state->setRedirect( 'taxonomy_manager.form', $redirectArray,
			$options );

	}

	/**
	 * Delete tid from table taxonomy_term_field_data
	 *
	 * @param array $form
	 * @param FormStateInterface $form_state
	 */
	public function table_form_delete(
		array &$form,
		FormStateInterface $form_state
	) {

		/** @var array $deleteArray */
		$deleteArray = $form['table']['#value'];

		foreach ( $deleteArray as $deleteValue ) {

			$this->service->deleteTidFromDB( $deleteValue );

			/** @var  $term */
			$term = Term::load( $deleteValue );
			if ( ! empty( $term ) ) {
				$term->delete();
			}
		}

		/** @var $vid */
		$vid = [
			'vid' => $form_state->getValue( 'vid' ),
		];

		/** @var  $options */
		$options = [];

		$form_state->setRedirect( 'taxonomy_manager.index', $vid, $options );

	}

	/**
	 * Combines two selected tids
	 *
	 * @param array $form
	 * @param FormStateInterface $form_state
	 */
	public function table_form_combine(
		array &$form,
		FormStateInterface $form_state
	) {

		/** @var $vereinenArray */
		$vereinenArray = $form['table']['#value'];

		/** @var $tidArray */
		$uebergabeArray = [];

		/** @var  $i */
		$i = 1;

		/** Add values to tidArray */
		foreach ( $vereinenArray as $key => $vereinenValue ) {

			/** @var  $valTid */
			$valTid                    = 'tid' . $i;
			$uebergabeArray[ $valTid ] = $vereinenValue;
			$i ++;

		}

		$uebergabeArray['vid'] = $form_state->getValue( 'vid' );

		/** @var $options */
		$options = [];

		$form_state->setRedirect( 'taxonomy_manager.merge', $uebergabeArray,
			$options );

	}
}