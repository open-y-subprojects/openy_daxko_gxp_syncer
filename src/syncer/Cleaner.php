<?php

namespace Drupal\openy_daxko_gxp_syncer\syncer;

/**
 * Cleanr for remove not existing schedules in api from database.
 */
class Cleaner {

  /**
   * Schedules wrapper.
   *
   * @var Wrapper
   */
  protected $wrapper;

  /**
   * Session manager.
   *
   * @var SessionManager
   */
  protected $sessionManager;

  /**
   * Mapping repository.
   *
   * @var \Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMappingRepository
   */
  protected $mappingRepository;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructor.
   *
   * @param Wrapper $wrapper
   *   Wrapper.
   * @param SessionManager $sessionManager
   *   Session manager.
   */
  public function __construct(Wrapper $wrapper, SessionManager $sessionManager) {
    $this->wrapper = $wrapper;
    $this->sessionManager = $sessionManager;
    $this->mappingRepository = $this->sessionManager->mappingRepository;
    $this->logger = $this->wrapper->logger;
  }

  /**
   * Clean.
   */
  public function clean() {
    $schedules = $this->wrapper->getSchedules();
    $schedulesToCreate = [];
    $schedulesToUpdate = [];

    $schedulesIds = array_column($schedules, 'id');

    // Remove not existing in api sessions.
    $mappingStorage = $this->mappingRepository->getStorage();
    $query = $mappingStorage->getQuery();
    $query->condition('gxpid', $schedulesIds, 'NOT IN');
    $ids = $query->execute();
    if (count($ids) > 0) {
      $msg = '[CLEANER] There are %total sessions which do not exist in api, trying to delete its from database.';
      $this->logger->debug($msg, [
        '%total' => count($ids),
      ]);
      $this->mappingRepository->deleteMappingByIds($ids);
    }

    // Check to existing schedules.
    $query = $mappingStorage->getQuery();
    $query->condition('gxpid', $schedulesIds, 'IN');
    $ids = $query->execute();
    $mappingsEntity = $this->mappingRepository->loadMultiple($ids);
    $mapping = [];
    /** @var \Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMappingInterface $mappingEntity */
    foreach ($mappingsEntity as $mappingEntity) {
      $mapping[$mappingEntity->getGxpId()] = $mappingEntity->getHash();
    }

    foreach ($schedules as $schedule) {
      if (!isset($mapping[$schedule['id']])) {
        $schedulesToCreate[$schedule['id']] = $schedule;
        continue;
      }
      if ($mapping[$schedule['id']] != $schedule['hash']) {
        $schedulesToUpdate[$schedule['id']] = $schedule;
      }
    }
    $msg = '[CLEANER] There are %total sessions to create.';
    $this->logger->debug($msg, ['%total' => count($schedulesToCreate)]);
    $msg = '[CLEANER] There are %total sessions to update.';
    $this->logger->debug($msg, ['%total' => count($schedulesToUpdate)]);
    // Set data to create new Sessions.
    $this->wrapper->setSchedulesToCreate($schedulesToCreate);
    // Set data to update Sessions.
    $this->wrapper->setSchedulesToUpdate($schedulesToUpdate);
  }

}
