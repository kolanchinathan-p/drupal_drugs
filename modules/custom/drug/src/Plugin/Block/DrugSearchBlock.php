<?php

namespace Drupal\drug\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Drug Search form' block.
 *
 * @Block(
 *   id = "drug_search_form_block",
 *   admin_label = @Translation("Drug Search form"),
 *   category = @Translation("Forms")
 * )
 */
class DrugSearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('Drupal\drug\Form\DrugSearchForm');
  }

}
