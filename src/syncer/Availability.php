<?php

namespace Drupal\openy_daxko_gxp_syncer\syncer;

use Drupal\openy_daxko_gxp_syncer\DaxkoGxpClient;

/**
 * Set availability from Daxko groupex api.
 *
 * @package Drupal\openy_daxko_gxp_syncer.
 */
class Availability {
  /**
   * Client.
   *
   * @var \Drupal\openy_daxko_gxp_syncer\DaxkoGxpClient
   */
  protected $client;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

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
   * Constructor.
   *
   * @param \Drupal\openy_daxko_gxp_syncer\DaxkoGxpClient $client
   *   Client.
   * @param Wrapper $wrapper
   *   Wrapper.
   * @param SessionManager $sessionManager
   *   Session manager.
   */
  public function __construct(DaxkoGxpClient $client, Wrapper $wrapper, SessionManager $sessionManager) {
    $this->client = $client;
    $this->wrapper = $wrapper;
    $this->sessionManager = $sessionManager;
    $this->mappingRepository = $this->sessionManager->mappingRepository;
    $this->config = $this->wrapper->config;
    $this->logger = $this->wrapper->logger;
  }

  /**
   * Actualizate availability data.
   */
  public function updateAvailability() {
    if ($this->config->get('enable_capacity_in_full_syncer')) {
      $this->logger->info('Availability enabled in full syncer. Please disable enable_capacity_in_full_syncer setting and clean all sessions before use availability syncer.');
      return;
    }
    $this->logger->info('[AVAILABILITY] Start update avalability for schedules');
    $begin = new \DateTime('now');
    $begin->setTimezone(new \DateTimeZone('America/Chicago'));

    $end = clone $begin;
    $end->modify('+' . $this->config->get('availability_days') . ' day');

    $goal = clone $end;
    $goal->modify('-1 day');
    $goal = $goal->format('Y-m-d');

    $interval = new \DateInterval('P1D');
    $daterange = new \DatePeriod($begin, $interval, $end);

    $storage = $this->mappingRepository->getStorage();
    foreach ($daterange as $date) {
      $date = $date->format('Y-m-d');
      $query = $storage->getQuery();
      $query->accessCheck(FALSE);
      $query->condition('day', $date);
      $query->condition('reservable', TRUE);
      $ids = $query->execute();
      $total = count($ids);
      $current = 1;
      $mappings = $storage->loadMultiple($ids);
      if ($total == 0) {
        $msg = '[AVAILABILITY] Reservable schedules not exist on %date in mapping, continue.';
        $this->logger->info($msg, [
          '%date' => $date,
        ]);
        continue;
      }
      foreach ($mappings as $mapping) {
        /** @var \Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMappingInterface $mapping */
        $msg = '[AVAILABILITY] Sync %date, goal %end. Try to get info for class %id. Step %step from %total';
        $this->logger->info($msg, [
          '%id' => $mapping->getGxpId(),
          '%date' => $date,
          '%end' => $goal,
          '%step' => $current,
          '%total' => $total,
        ]);
        $current += 1;
        $scheduleDetails = $this->client->getScheduleDetails($mapping->getGxpId());
        if (!isset($scheduleDetails["brief"])) {
          $msg = '[AVAILABILITY] Unexpected schedule data from api for %id on date %date. Data %data';
          $this->logger->warning($msg, [
            '%id' => $mapping->getGxpId(),
            '%date' => $date,
            '%data' => serialize($scheduleDetails),
          ]);
          continue;
        }
        if (!$scheduleDetails["brief"]["reservable"]) {
          $msg = '[AVAILABILITY] Schedule %id not more reservable. wait for openy_daxko_gxp_syncer.syncer to update session';
          $this->logger->info($msg, ['%id' => $mapping->getGxpId()]);
          continue;
        }
        $booking = $scheduleDetails["bookingDetails"];
        $availabilityStatus = 'class full';
        $availability = $booking["capacity"] - $booking["booked"];
        if ($availability > 0) {
          $availabilityStatus = $availability . ' spots left';
        }
        $waitlist = $booking["waitlistCapacity"] - $booking["waitlistBooked"];
        if ($waitlist > 0) {
          // @todo disaplay waitlist availability.
          $availabilityStatus = 'waitlist only';
        }
        if ($mapping->getAvailabilty() != $availabilityStatus) {
          $oldValue = $mapping->getAvailabilty();
          $mapping->setAvailabilty($availabilityStatus);
          $mapping->save();
          $this->logger->info('[AVAILABILITY] Schedule %id on date %date updated. Old: "%oldValue", New: "%newValue"', [
            '%id' => $mapping->getGxpId(),
            '%date' => $date,
            '%newValue' => $availabilityStatus,
            '%oldValue' => (string) $oldValue,
          ]);
        }
      }
    }
    $this->logger->info('[AVAILABILITY] End update avalability for schedules');
  }

}
