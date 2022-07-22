<?php

namespace Drupal\drug\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Component\Utility\Unicode;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Builds the search form for the search block.
 *
 * @internal
 */
class DrugSearchFilterForm extends FormBase {

  /**
   * The search page repository.
   *
   * @var \Drupal\search\SearchPageRepositoryInterface
   */
  protected $searchPageRepository;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;



  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drug_search_filter_form';
  }


  public function buildForm(array $form, FormStateInterface $form_state, array $drug_rslt = NULL) {


    $pharmacyPrices = $drug_rslt['pharmacyPrices'];
    $forms = $drug_rslt['forms'];
    $strengths = $drug_rslt['strengths'];



    if(!empty($drug_rslt->drug)){
      $bgFlag = !empty($drug_rslt->drug->bgFlag) ? $drug_rslt->drug->bgFlag : '';
    }


    //Brand / Generic Options
    if(!empty($drug_rslt['drug'])){
      foreach($drug_rslt['drug'] as $brand){
        $brand_options[$brand['brandgeneric_code'] .'_'. $brand['DrugName']] = ucfirst($brand['genericDrugName'] .' ( '. $brand['brandgeneric_desc'] .' )');
      }

    }

    //Form Options
    if(!empty($drug_rslt['forms'])){
      $form_options = $drug_rslt['forms'];
    }

     //Strengths Options
     if(!empty($drug_rslt['strengths'])){
       $str_options = $drug_rslt['strengths'];
     }

    $form['bdrugnameFilter'] = [
      '#type' => 'select',
      '#title' => $this->t('Brand / Generic'),
      '#default_value' => $default_brand_opt,
      '#options' => $brand_options,
      '#validated' => TRUE,
    ];


    $form['drugformFilter'] = [
      '#type' => 'select',
      '#title' => $this->t('Form'),
      '#default_value' => $default_form_opt,
      '#options' => $form_options,
      '#validated' => TRUE
    ];

    $form['drugstrFilter'] = [
      '#type' => 'select',
      '#title' => $this->t('Dosage'),
      '#default_value' => $default_str_opt,
      '#options' => $str_options,
      '#validated' => TRUE,
    ];

    if(!empty($default_form_opt)){
      $form_opt = $form_options[$default_form_opt];
    }
    else {
      $form_opt = reset($form_options);
    }
    $qty_options = ['1' => '1 '.$form_opt, '2' => '2 '.$form_opt,'5' => '5 '.$form_opt, '10' => '10 '.$form_opt];

    if(!empty(\Drupal::request()->get('quantity'))){
      $quantity = \Drupal::request()->get('quantity');
      if(!array_key_exists($quantity, $qty_options)){
        $default_qty_opt = 'custom';
        $default_cus_qty = $quantity;
      }
    }
    $qty_options['custom'] = 'Custom';
    $form['drugquantFilter'] = [
      '#type' => 'select',
      '#title' => $this->t('Quantity'),
      '#default_value' => $default_qty_opt,
      '#options' => $qty_options,
      '#validated' => TRUE,
    ];

    $form['custom_quantity'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cus Qty'),
      '#states' => [
        'visible' => [
          'select[name="drugquantFilter"]' => ['value' => 'custom']
        ],
      ],
      '#attributes' => array('maxlength' => 4, 'size' => 4),
      '#default_value' => $default_cus_qty,
    ];

    $filter_type = !empty(\Drupal::request()->request->get('filterType')) ? \Drupal::request()->request->get('filterType') : '';

    $form['filterType'] = array(
      '#type' => 'hidden',
      '#value' => \Drupal::request()->request->get('filterType'),
    );



    // $form['drugGSN'] = array(
    //   '#type' => 'hidden',
    //   '#value' => $gsn,
    // );
    // $form['bgFlag'] = array(
    //   '#type' => 'hidden',
    //   '#value' => $bgFlag,
    // );



    $form['#cache'] = ['max-age' => -1];

    return $form;


  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $drug_name = $form_state->getValue('drug_name');
    $drug_name = str_replace('/', '___',$drug_name);

    $form_state->setRedirect(
      'drug.search',
      [
        'drug_name' => $drug_name
      ]
    );

  }


}
