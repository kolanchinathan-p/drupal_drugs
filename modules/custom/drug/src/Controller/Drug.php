<?php
/**
 * @file
 * Contains \Drupal\drug\Controller\search .
 */

namespace Drupal\drug\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Url;


class Drug extends ControllerBase {

  public function search(Request $request, $drug_name){

   $options=[];
   $filterType = $medId = '';

   if(!empty($drug_name)){

     $connection = \Drupal::database();

     // Escape for LIKE matching.
     $drug_name = \Drupal::database()->escapeLike($drug_name);

     // Replace wildcards with MySQL/PostgreSQL wildcards.
     $drug_name = preg_replace('!\*+!', '%', $drug_name);


     if(!empty(\Drupal::request()->request->get('filterType'))){

       $filterType = \Drupal::request()->request->get('filterType');
       $request_filter['filterType'] = $filterType;

       switch($filterType){

         case 'form':
           if(!empty(\Drupal::request()->request->get('drugformFilter'))){
             $medId = (!empty(\Drupal::request()->request->get('drugformFilter'))) ? \Drupal::request()->request->get('drugformFilter') : '';
           }

         break;
         case 'strength':
           if(!empty(\Drupal::request()->request->get('drugstrFilter'))){
             $medId = (!empty(\Drupal::request()->request->get('drugstrFilter'))) ? \Drupal::request()->request->get('drugstrFilter') : '';
           }
         break;
         case 'quantity':

             if(!empty(\Drupal::request()->request->get('drugquantFilter'))){
               if(!empty(\Drupal::request()->request->get('drugquantFilter')) && (\Drupal::request()->request->get('drugquantFilter') != 'custom')){
                 $quantity = \Drupal::request()->request->get('drugquantFilter');
               }
               else if(!empty(\Drupal::request()->request->get('custom_quantity'))){
                 $quantity = \Drupal::request()->request->get('custom_quantity');
               }

             }

         break;
       }
     }

    $query = $connection->select('drugs', 'd');
    $query->join('prices', 'pr', 'd.NDC = pr.NDC');
    $query
      ->fields('d', array('MEDID','NDC', 'DrugName','Slug', 'genericDrugName', 'formLabel', 'strength', 'brandgeneric_code', 'brandgeneric_desc'))
      ->fields('pr', array('NDC','AffilID', 'MegaChainNameNew','unit_cost', 'unit_retail_price'));

    $query->condition($query->orConditionGroup()
         ->condition('d.DrugName',  $drug_name , '=')
         ->condition('d.Slug', $drug_name , '=')
      );

    if((!empty($filterType) && !empty($medId)) && ($filterType == 'strength' || $filterType == 'form')){
      $query->condition('d.MEDID',  $medId , '=');
    }

     $query->condition('pr.AffilID',  '0000000' , '!=');
     $query->condition('pr.AffilID', 0 , '!=');


      $responses = $query->execute()->fetchAll();
      $rslt = $output = [];
      if(!empty($responses)){

      }
      foreach($responses as $res){

        $output['forms'][$res->MEDID] = $res->formLabel;
        $output['strengths'][$res->MEDID] = $res->strength;
        $rslt['pharmacy'][$res->AffilID] =  $res->MegaChainNameNew;

        if(!empty($res->unit_cost)){
          $unit_cost = $res->unit_cost;
        }
        if(!empty($res->unit_retail_price)){
          $unit_retail_price = $res->unit_retail_price;
        }

        if(!empty($quantity) && $quantity > 1){
          $unit_cost = ($unit_cost * $quantity);
          $unit_retail_price = ($unit_retail_price * $quantity);
        }


        $rslt['prices'][$res->AffilID] =  ['MegaChainName' => $res->MegaChainNameNew, 'unit_cost' => $unit_cost,  'unit_retail_price' => $unit_retail_price];
        $output['drug'][$res->Slug] = ['DrugName' => $res->DrugName, 'genericDrugName' => $res->genericDrugName, 'brandgeneric_code' => $res->brandgeneric_code, 'brandgeneric_desc' => $res->brandgeneric_desc];

      }
      if(!empty($rslt['pharmacy'])){

        $pharmacies =  array_unique($rslt['pharmacy']);

        $prices = !empty($rslt['prices']) ? $rslt['prices'] : [];

        foreach ($pharmacies as $AffilID => $MegaChainName) {
          //Sub Query to find Unique Pharmacy
          $sub_ph_pr_query = \Drupal::database()->select('pharmacies', 'phs');
          $sub_ph_pr_query->addExpression("MAX(phs.NPI)", 'NPI');
          $sub_ph_pr_query->condition($sub_ph_pr_query->andConditionGroup()
              ->condition('phs.MegaChainName', $MegaChainName, '=')
              ->condition('phs.PharmAffil_id', $AffilID, '=')
            );
          // To Get Pharmacy information
          $ph_pr_query = $connection->select('pharmacies', 'ph');
          $ph_pr_query->fields('ph', array('NPI', 'PharmAffil_id', 'PharmaName', 'MegaChainName', 'Address1','City', 'State', 'PostalCode'));
          $ph_pr_query->condition('ph.NPI', $sub_ph_pr_query, 'IN');

          //$ph_pr_query->having('COUNT(ph.State) >= :matches', [':matches' => 2]);

          $ph_pr_res = $ph_pr_query->distinct()->execute()->fetchAll();

          if(isset($prices[$AffilID])){
           $rslt_price = ['unit_cost' => $prices[$AffilID]['unit_cost'], 'unit_retail_price' => $prices[$AffilID]['unit_retail_price']];
          }

          $output['pharmacyPrices'][]=['pharmacy' => $ph_pr_res[0], 'price' => $rslt_price];
        }
      }


     //Get Unique Forms values
      if(!empty($output['forms'])){
        $output['forms'] = array_unique($output['forms']);
      }
      //Get Unique Strengths values
      if(!empty($output['strengths'])){
        $output['strengths'] = array_unique($output['strengths']);
      }

    }

   //Drug filter Form
   $build['search_filter_form'] = \Drupal::formBuilder()->getForm('\Drupal\drug\Form\DrugSearchFilterForm', $output);


   $return_content = array(
     '#theme' => 'drug_search_results',
     '#items' => $output['pharmacyPrices'],
     '#names' => $output['forms'],
     '#quantities' => $quantities,
     '#strengths' => $output['strengths'],
     '#forms' => $output['forms'],
     '#search_filter_form' => $build['search_filter_form'],
     '#drugName' => mb_strtoupper($drug_name),
     '#cache' => [
       'contexts' => [],
       'tags' => [],
       'max-age' => -1,
     ],
   );
   $return_content['#attached']['library'][] = 'drug/drug';

   return $return_content;


  }


  public function autoComplete(Request $request, $field_name, $site_name = NULL) {
    $count = 10;

      $results = $response = [];


      $curatedsearch = $keywords = $drugSugg = $drugSuggestions = [];

      // Get the typed string from the URL, if it exists.
      if ($input = $request->query->get('q')) {

        $typed_string = mb_strtoupper($input);

        if(strlen($typed_string) > 2){

          // Process the keywords.
          $keys = $typed_string;

          // Escape for LIKE matching.
          $keys = \Drupal::database()->escapeLike($keys);

          // Replace wildcards with MySQL/PostgreSQL wildcards.
          $keys = preg_replace('!\*+!', '%', $keys);

          $connection = \Drupal::database();
          $query = $connection->select('drugs', 'd');
          $query->fields('d', ['DrugName', 'Slug']);
          $query->condition($query->orConditionGroup()
            ->condition('DrugName', '%' . $keys . '%', 'LIKE')
            ->condition('Slug', '%' . $keys . '%', 'LIKE')
          );
          $responses = $query->distinct()->execute()->fetchAll();

          if(!empty($responses) && count($responses) > 0){
            foreach ($responses as $drug) {
              $results[] = [
                'value' => $drug->DrugName,
                'label' => $drug->DrugName,
                'url' => $drug->Slug,
              ];
            }
          }
        }
      }

      return new JsonResponse($results);
   }


}
