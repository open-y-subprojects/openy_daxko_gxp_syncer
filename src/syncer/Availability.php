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
    $this->logger->info('Start update avalability for schedules');
    $begin = new \DateTime('now');
    $begin->setTimezone(new \DateTimeZone(date_default_timezone_get()));

    $end = clone $begin;
    $end->modify('+3 day');
    $interval = new \DateInterval('P1D');
    $daterange = new \DatePeriod($begin, $interval, $end);

    $storage = $this->mappingRepository->getStorage();
    foreach ($daterange as $date) {
      $date = $date->format('Y-m-d');
      $query = $storage->getQuery();
      $query->condition('day', $date);
      $query->condition('reservable', TRUE);
      $ids = $query->execute();
      $mappings = $this->mappingRepository->loadMultiple($ids);
      foreach ($mappings as $mapping) {
        /** @var \Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMappingInterface $mapping */
        $scheduleDetails = $this->client->getScheduleDetails($mapping->getGxpId());
        if (!isset($scheduleDetails["brief"])) {
          continue;
        }
        if (!$scheduleDetails["brief"]["reservable"]) {
          $msg = 'Schedule %id not more reservable. wait for openy_daxko_gxp_syncer.syncer to update session';
          $this->logger->info($msg, ['%id' => $scheduleDetails["brief"]["id"]]);
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
          $mapping->setAvailabilty($availabilityStatus);
          $mapping->save();
          $this->logger->info('Avalability for mapping %id on date %date updated', ['%id' => $mapping->id(), '%date' => $date]);
        }
      }
    }
    $this->logger->info('End update avalability for schedules');
  }

}
