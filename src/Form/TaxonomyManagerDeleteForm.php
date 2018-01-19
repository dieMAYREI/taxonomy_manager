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
class TaxonomyManagerDeleteForm extends TaxonomyManagerAbstractForm {

  /** @var $vid */
  private $vid;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_manager.deleteForm';
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
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
      if (NULL !== $term) {
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
    $tid = \Drupal::request()->get('tid');
    $form['label'] = [
      '#type'        => 'item',
      '#description' => 'In folgenden Feldern werden Aktualisierungen durchgeführt:',
    ];

    $this->service->getSingleTidName($tid);

    $rows[] = '';

    $tables = $this->service->getTablesToUpdate();
    $counter = 0;
    foreach($tables as $table => $field){

      $occurrences = $this->service->getOccurrences($table, $field, $tid);
      $occurrences = count($occurrences);

      if($occurrences > 0){
        $rows[$counter] = [
          'tablename' => $table,
          'tag_name' => $name,
          'occurrences' => $occurrences
        ];
      }

      $counter++;
    }

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

    /** tid textfield */
    $form['tid'] = [
      '#type'     => 'textfield',
      '#title'    => t('tid:'),
      '#value'    => $tid,
      '#disabled' => TRUE,
    ];

    /** name textfield */
    $form['name'] = [
      '#type'     => 'textfield',
      '#title'    => t('name:'),
      '#value'    => $name,
      '#disabled' => TRUE,
    ];

    /** vid textfield */
    $form['vid'] = [
      '#type'     => 'textfield',
      '#title'    => t('vid:'),
      '#value'    => $vid,
      '#disabled' => TRUE,
    ];

    /** langcode textfield */
    $form['langcode'] = [
      '#type'     => 'textfield',
      '#title'    => t('langcode:'),
      '#value'    => $langcode,
      '#disabled' => TRUE,
    ];

    /** Text output */
    $form['reallyDelete'] = [
      '#type'  => 'item',
      '#title' => $this->t('Really Delete?'),
    ];

    /** AbbrechenButton */
    $form['Abbrechen'] = [
      '#type'        => 'submit',
      '#value'       => $this->t('Abbrechen'),
      '#button_type' => 'primary',
      '#submit'      => ['::cancelSubmitHandler'],
      '#attributes'  => ['style' => 'margin-left: 0px;'],
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
   * @param array $form
   * @param FormStateInterface $form_state
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->service->deleteTidFromDB($form_state->getValue('tid'));

    /** @var Term $term */
    $term = Term::load($form_state->getValue('tid'));

    $name = '';
    if ($term !== NULL) {
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

    drupal_set_message($name . ' deleted!', 'status', TRUE);

    $form_state->setRedirect('taxonomy_manager.index', $vid, $options);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
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

  public function cancelSubmitHandler(array &$form, FormStateInterface $form_state) {
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