<?php

namespace Drupal\openy_daxko_gxp_syncer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Provides a view controller for a daxko groupex mapping entity type.
 */
class DaxkoGroupexMappingViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);
    // The daxko groupex mapping has no entity template itself.
    unset($build['#theme']);
    return $build;
  }

}
