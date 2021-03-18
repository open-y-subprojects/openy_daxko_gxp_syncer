<?php

namespace Drupal\openy_daxko_gxp_syncer\syncer;

/**
 * Divide schedules for create, update, and delete by queue.
 */
class QueueManager {

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
  public function manage() {
    // TODO use dependecy injection.
    $queue = \Drupal::queue('openy_daxko_gxp');
    $queue->deleteQueue();
    $schedules = $this->wrapper->getSchedules();

    $schedulesIds = array_column($schedules, 'id');

    $mappingStorage = $this->mappingRepository->getStorage();

    $totalToDelete = 0;
    $totalToCreate = 0;
    $totalToUpdate = 0;
    $query = $mappingStorage->getQuery();
    $query->condition('gxpid', $schedulesIds, 'NOT IN');
    $ids = $query->execute();
    foreach ($ids as $mappingId) {
      $itemToDelete = [
        'action' => 'delete',
        'mapping' => $mappingStorage->load($mappingId),
      ];
      $queue->createItem($itemToDelete);
      $totalToDelete += 1;
    }

    // Check to existing schedules.
    $query = $mappingStorage->getQuery();
    $query->condition('gxpid', $schedulesIds, 'IN');
    $ids = $query->execute();
    $mappingsEntity = $mappingStorage->loadMultiple($ids);
    $mapping = [];
    /** @var \Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMappingInterface $mappingEntity */
    foreach ($mappingsEntity as $mappingEntity) {
      $mapping[$mappingEntity->getGxpId()] = $mappingEntity->getHash();
    }

    foreach ($schedules as $schedule) {
      if (!isset($mapping[$schedule['id']])) {
        $schedule['action'] = 'create';
        $queue->createItem($schedule);
        $totalToCreate += 1;
        continue;
      }
      if ($mapping[$schedule['id']] != $schedule['hash']) {
        $schedule['action'] = 'update';
        $queue->createItem($schedule);
        $totalToUpdate += 1;
      }
    }
    $msg = '[QUEUEMANAGER] There are %total schedules to process.';
    $this->logger->debug($msg, ['%total' => $queue->numberOfItems()]);
    $msg = '[QUEUEMANAGER] There are %total sessions to delete.';
    $this->logger->debug($msg, ['%total' => $totalToDelete]);
    $msg = '[QUEUEMANAGER] There are %total sessions to create.';
    $this->logger->debug($msg, ['%total' => $totalToCreate]);
    $msg = '[QUEUEMANAGER] There are %total sessions to update.';
    $this->logger->debug($msg, ['%total' => $totalToUpdate]);
  }

}
