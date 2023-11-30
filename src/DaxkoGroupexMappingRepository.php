<?php

namespace Drupal\openy_daxko_gxp_syncer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\node\Entity\Node;

/**
 * Daxko Groupex Mapping Repository.
 *
 * @package Drupal\openy_daxko_gxp_syncer
 */
class DaxkoGroupexMappingRepository {

  const STORAGE = 'daxko_groupex_mapping';
  const CHUNK_SIZE = 50;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  public $entityTypeManager;

  /**
   * Entity Storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  public $storage;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  public $logger;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Logger.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerChannelInterface $logger) {
    $this->entityTypeManager = $entityTypeManager;
    $this->storage = $this->entityTypeManager->getStorage(self::STORAGE);
    $this->logger = $logger;
  }

  /**
   * Delete all mapping and remove all groupex session.
   */
  public function deleteMapping() {
    $queue = \Drupal::queue('openy_daxko_gxp');
    $queue->deleteQueue();

    $ids = $this->storage->getQuery()->accessCheck(FALSE)->execute();
    $this->logger->notice('[REPOSITORY] Trying to remove all sessions from Daxko Groupex Mapping.');
    if (empty($ids)) {
      $this->logger->notice('[REPOSITORY] Nothing to delete, repository is empty.');
      return;
    }
    $this->deleteMappingByIds($ids);
  }

  /**
   * Delete Mappings and node session by mapping ids.
   *
   * @param array $ids
   *   Daxko Groupex Mapping ids.
   */
  public function deleteMappingByIds(array $ids) {
    $chunks = array_chunk($ids, self::CHUNK_SIZE);
    $total = count($ids);
    $left = count($ids);
    $steps = count($chunks);
    $step = 1;
    foreach ($chunks as $chunk) {
      $entities = $this->storage->loadMultiple($chunk);
      $msg = '[REPOSITORY] Step %step from %steps for delete sessions from database. Total sessions: %total Left: %left';
      $this->logger->debug($msg, [
        '%step' => $step,
        '%steps' => $steps,
        '%total' => $total,
        '%left' => $left,
      ]);
      $step += 1;
      /** @var \Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMappingInterface $entity */
      foreach ($entities as $entity) {
        $session = $entity->getSession();
        $session->delete();
        $entity->delete();
      }
      $left -= count($chunk);
    }
    $msg = '[REPOSITORY] There are %total sessions was deleted from database.';
    $this->logger->notice($msg, [
      '%total' => $total,
    ]);
  }

  /**
   * Load mapping by groupex id.
   *
   * @param string $gxpId
   *   Unique id for schedules from Daxko API.
   *
   * @return \Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMapping
   *   Daxko Groupex Mapping entity.
   */
  public function loadByGxpId(string $gxpId) {
    $mapping = $this->storage->loadByProperties(['gxpid' => $gxpId]);
    $mapping = reset($mapping);
    return $mapping;
  }

  /**
   * Get DaxkoGroupexMapping storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   Return storage for daxko_groupex_mapping entity.
   */
  public function getStorage() {
    return $this->storage;
  }

  /**
   * Create mapping.
   *
   * @param \Drupal\node\Entity\Node $session
   *   NOde \Drupal\node\Entity\Node.
   * @param string $locationId
   *   Id of node with type branch.
   * @param string $gxpId
   *   Unique id for schedules from Daxko API.
   * @param string $hash
   *   Hash of schedule data api.
   * @param bool $reservable
   *   Is reservable.
   * @param string $day
   *   Day in Y-m-d format.
   * @param string $availabilityStatus
   *   Availability status.
   *
   * @return \Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMapping
   *   Daxko Groupex Mapping entity.
   */
  public function create(Node $session, string $locationId, string $gxpId, string $hash, bool $reservable, string $day, $availabilityStatus = NULL) {
    /** @var \Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMappingInterface $mapping */
    $mapping = $this->storage->create();
    $mapping->setSession($session);
    $mapping->setLocationId($locationId);
    $mapping->setGxpId($gxpId);
    $mapping->setHash($hash);
    if ($reservable) {
      $mapping->isReservable();
    }
    $mapping->setDay($day);
    $mapping->setAvailabilty($availabilityStatus);
    $mapping->save();
    return $mapping;
  }

}
