<?php

namespace Drupal\openy_daxko_gxp_syncer;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\node\Entity\Node;

/**
 * Provides an interface defining a daxko groupex mapping entity type.
 */
interface DaxkoGroupexMappingInterface extends ContentEntityInterface {

  /**
   * Gets the daxko groupex mapping groupex id.
   *
   * @return string
   *   Groupex id of the daxko api.
   */
  public function getGxpId();

  /**
   * Sets the daxko groupex mapping groupex id.
   *
   * @param string $gxpId
   *   Groupex id of the daxko api.
   *
   * @return \Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMappingInterface
   *   The called daxko groupex mapping entity.
   */
  public function setGxpId($gxpId);

  /**
   * Gets the daxko groupex mapping schedule hash.
   *
   * @return string
   *   Schedule hash of the daxko groupex mapping.
   */
  public function getHash();

  /**
   * Sets the daxko groupex mapping schedule hash.
   *
   * @param string $hash
   *   The daxko groupex mapping schedule hash.
   *
   * @return \Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMappingInterface
   *   The called daxko groupex mapping entity.
   */
  public function setHash($hash);

  /**
   * Gets the daxko groupex mapping location id.
   *
   * @return string
   *   Location id of the daxko groupex mapping.
   */
  public function getLocationId();

  /**
   * Sets the daxko groupex mapping location id.
   *
   * @param string $locationId
   *   The daxko groupex mapping location id.
   *
   * @return \Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMappingInterface
   *   The called daxko groupex mapping entity.
   */
  public function setLocationId($locationId);

  /**
   * Gets the daxko groupex mapping session node.
   *
   * @return \Drupal\node\Entity\Node
   *   Session node of the daxko groupex mapping.
   */
  public function getSession();

  /**
   * Sets the daxko groupex mapping reference to node session.
   *
   * @param \Drupal\node\Entity\Node $session
   *   The daxko groupex mapping reference to node session.
   *
   * @return \Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMappingInterface
   *   The called daxko groupex mapping entity.
   */
  public function setSession(Node $session);

}
