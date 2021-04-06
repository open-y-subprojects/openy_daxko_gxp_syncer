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
   * Gets the daxko groupex mapping day.
   *
   * @return string
   *   Day in Y-m-d format.
   */
  public function getDay();

  /**
   * Sets the daxko groupex mapping day.
   *
   * @param string $day
   *   Day in Y-m-d format.
   *
   * @return \Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMappingInterface
   *   The called daxko groupex mapping entity.
   */
  public function setDay($day);

  /**
   * Gets the daxko groupex mapping reservable status.
   *
   * @return bolean
   *   Reservable status.
   */
  public function getReservable();

  /**
   * Sets the daxko groupex mapping reservable.
   *
   * @return \Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMappingInterface
   *   The called daxko groupex mapping entity.
   */
  public function isReservable();

  /**
   * Sets the daxko groupex mapping not reservable.
   *
   * @return \Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMappingInterface
   *   The called daxko groupex mapping entity.
   */
  public function unReservable();

  /**
   * Gets the daxko groupex mapping availabilty status.
   *
   * @return string
   *   Text of availabilty status.
   */
  public function getAvailabilty();

  /**
   * Sets the daxko groupex mapping availabilty status.
   *
   * @param string $text
   *   Availabilty status text.
   *
   * @return \Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMappingInterface
   *   The called daxko groupex mapping entity.
   */
  public function setAvailabilty($text);

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
