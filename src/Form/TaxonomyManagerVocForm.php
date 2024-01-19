<?php

namespace Drupal\taxonomy_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Class TaxonomyManagerVocForm
 *
 * @package Drupal\taxonomy_manager\Form
 */
class TaxonomyManagerVocForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_manager_vocform';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $vocabularies = Vocabulary::loadMultiple();

    $header = [
      'name' => t('Vocabulary Name'),
    ];

    $options = [];

    foreach ($vocabularies as $vocabulary) {
      $url = Url::fromRoute('taxonomy_manager.index', ['vid' => $vocabulary->id()]);
      $options[$vocabulary->id()] = [
        'name' => Link::fromTextAndUrl($vocabulary->label(), $url)->toString(),
      ];
    }

    $form['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}
}
