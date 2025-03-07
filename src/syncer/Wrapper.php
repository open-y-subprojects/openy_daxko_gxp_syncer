<?php

namespace Drupal\openy_daxko_gxp_syncer\syncer;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\openy_mappings\LocationMappingRepository;

/**
 * Enrich and normilize data from daxko groupex api for create sessions.
 *
 * @package Drupal\openy_daxko_gxp_syncer/syncer.
 */
class Wrapper {

  /**
   * Config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  public $config;

  /**
   * Location Mapping repository.
   *
   * @var \Drupal\openy_mappings\LocationMappingRepository
   */
  protected $locationRepository;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  public $logger;

  /**
   * Array of schedules.
   *
   * @var array
   */
  protected $schedules;

  /**
   * Array of schedules to create.
   *
   * @var array
   */
  protected $schedulesToCreate;

  /**
   * Array of schedules to update.
   *
   * @var array
   */
  protected $schedulesToUpdate;

  /**
   * Array of locations data.
   *
   * @var array
   */
  protected $locationMapping;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Config.
   * @param \Drupal\ymca_mappings\LocationMappingRepository $locationRepository
   *   YN Location repositore.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Logger.
   */
  public function __construct(ImmutableConfig $config, LocationMappingRepository $locationRepository, LoggerChannelInterface $logger) {
    $this->config = $config;
    $this->locationRepository = $locationRepository;
    $this->locationMapping = [];
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchedules() {
    return $this->schedules;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchedulesToCreate() {
    return $this->schedulesToCreate;
  }

  /**
   * {@inheritdoc}
   */
  public function setSchedulesToCreate($schedules) {
    $this->schedulesToCreate = $schedules;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchedulesToUpdate() {
    return $this->schedulesToUpdate;
  }

  /**
   * {@inheritdoc}
   */
  public function setSchedulesToUpdate($schedules) {
    $this->schedulesToUpdate = $schedules;
  }

  /**
   * {@inheritdoc}
   */
  public function addSchedules($rawData) {
    $schedules = [];
    $locationMapping = $this->getLocationMapping();

    // Remove not needed brief key from api data.
    foreach ($rawData as $schedule) {
      if (isset($schedule['brief'])) {
        $schedules[$schedule['brief']['id']] = $schedule['brief'];
      }
    }
    // Normalize fields.
    foreach ($schedules as $key => &$schedule) {
      if (!$this->isScheduleValid($schedule)) {
        unset($schedules[$key]);
        continue;
      }
      $schedule['locationId'] = $locationMapping[$schedule['locationId']]['branchId'];
      // In Groupex UI we can set only one instructor or activity.
      $schedule['instructor'] = reset($schedule['instructor']);
      $schedule['activity'] = reset($schedule['activity']);

      // Remove Â symbol.
      $schedule['activity'] = str_replace('Â', '', $schedule['activity']);
      $schedule['name'] = str_replace('Â', '', $schedule['name']);

      if ($schedule['reservable'] && $this->config->get('enable_capacity_in_full_syncer')) {
        $availabilityStatus = 'class full';
        $availability = $schedule["capacity"] - $schedule["booked"];
        $waitlist = $schedule["waitlistCapacity"] - $schedule["waitlistBooked"];
        if ($waitlist > 0) {
          // @todo disaplay waitlist availability.
          $availabilityStatus = 'waitlist only';
        }
        if ($availability > 0) {
          $availabilityStatus = $availability . ' spots left';
        }
        $schedule['availabilityStatus'] = $availabilityStatus;
      }
      if ($this->config->get('enable_capacity_in_full_syncer')) {
        unset($schedule["capacity"]);
        unset($schedule["booked"]);
        unset($schedule["waitlistCapacity"]);
        unset($schedule["waitlistBooked"]);
      }

      // Get reservation id for schedule. Last 6 symbols in id is a date.
      if ($schedule['reservable']) {
        $schedule['reservationId'] = substr($schedule['id'], 0, -6);
      }
      // Conver datetime.
      $schedule['weekDay'] = new \DateTime($schedule['startDateTime'], new \DateTimeZone('utc'));
      $schedule['weekDay']->setTimezone(new \DateTimeZone('America/Chicago'));
      $schedule['weekDay'] = strtolower($schedule['weekDay']->format('l'));
      $schedule['day'] = new \DateTime($schedule['startDateTime'], new \DateTimeZone('utc'));
      $schedule['day']->setTimezone(new \DateTimeZone('America/Chicago'));
      $schedule['day'] = $schedule['day']->format('Y-m-d');
      $schedule['startDateTime'] = new \DateTime($schedule['startDateTime'], new \DateTimeZone('utc'));
      $schedule['startDateTime'] = $schedule['startDateTime']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
      $schedule['endDateTime'] = new \DateTime($schedule['endDateTime'], new \DateTimeZone('utc'));
      $schedule['endDateTime'] = $schedule['endDateTime']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

      // Add hash for check changes in cleaner.
      $schedule['hash'] = hash('sha256', serialize($schedule));
    }
    $this->schedules = $schedules;
  }

  /**
   * Get locations mapping.
   *
   * @return array
   *   Locations mapping with branch id and title.
   */
  public function getLocationMapping() {
    if (count($this->locationMapping) > 0) {
      return $this->locationMapping;
    }
    $gxpLocations = $this->locationRepository->loadAllLocationsWithGroupExId();
    $locationMapping = [];
    foreach ($gxpLocations as $mapping) {
      $branch = $mapping->field_location_ref->referencedEntities();
      $branch = reset($branch);
      $locationMapping[$mapping->get('field_groupex_id')->value] = [
        'branchId' => $branch->id(),
        'title' => $branch->title->value,
      ];
    }
    $this->locationMapping = $locationMapping;
    return $locationMapping;
  }

  /**
   * Check if schedule is valid.
   *
   * @param mixed $schedule
   *   Schedule.
   *
   * @return bool
   *   Is valid.
   */
  private function isScheduleValid($schedule) {
    $locationMapping = $this->getLocationMapping();
    if (!isset($locationMapping[$schedule['locationId']])) {
      $msg = 'We have not Groupex location id %id in locations mapping, continue. Data: %data';
      $this->logger->warning($msg, [
        '%id' => $schedule['locationId'],
        '%data' => json_encode($schedule),
      ]);
      return FALSE;
    }
    if (empty($schedule['name'])) {
      $msg = 'No name in schedule data with id %id, continue. Data: %data';
      $this->logger->warning($msg, [
        '%id' => $schedule['id'],
        '%data' => json_encode($schedule),
      ]);
      return FALSE;
    }

    return TRUE;
  }

}
