<?php

namespace Drupal\drug\Twig;

use Drupal\Core\Entity\EntityInterface;

/**
 * Twig extension with some useful functions and filters.
 */
class TwigExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('render', [$this, 'render']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'drug';
  }

  public function render($field_name) {
    return \Drupal::service('renderer')->render($field_name);
  }


}
