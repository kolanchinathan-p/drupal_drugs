<?php

namespace Drupal\drug\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the search form for the search block.
 *
 * @internal
 */
class DrugSearchForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drug_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {


    $form['drug_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Drug Name Generic or Brand'),
      '#autocomplete_route_name' => 'drug.autocomplete',
      '#autocomplete_route_parameters' => array('field_name' => 'drug_name'),
      '#attributes' => [
        'class' => ['form-control drug-text drug-name'],
        'autocorrect' => 'off',
        'autocapitalize' => 'off',
        'spellcheck' => 'false',
        'autocomplete' => 'off',
      ],
    ];

    $form['#attached']['library'][] = 'drug/drug';


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $drug_name = $form_state->getValue('drug_name');
    $drug_name = mb_strtolower(str_replace('/', '___', $drug_name));
    $drug_name = trim($drug_name);
    $drug_name = ltrim($drug_name, ".");
    $drug_name = rtrim($drug_name, "! \"'.,");
    $form_state->setRedirect(
      'drug.search',
      [
        'site' => mb_strtolower($siteName),
        'drug_name' => $drug_name
      ]
    );
    // This form submits to the search page, so processing happens there.
  }

}
