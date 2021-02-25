<?php

namespace Drupal\openy_daxko_gxp_syncer\syncer;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\ymca_mappings\LocationMappingRepository;

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
   * @var \Drupal\ymca_mappings\LocationMappingRepository
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
    foreach ($schedules as &$schedule) {
      if (!isset($locationMapping[$schedule['locationId']])) {
        $msg = 'We have not Groupex location id %id in locations mapping, continue. Data: %data';
        $this->logger->warning($msg, [
          '%id' => $schedule['locationId'],
          '%data' => json_encode($schedule),
        ]);
        unset($schedule);
        continue;
      }
      $schedule['locationId'] = $locationMapping[$schedule['locationId']]['branchId'];
      // In Groupex UI we can set only one instructor or activity.
      $schedule['instructor'] = reset($schedule['instructor']);
      $schedule['activity'] = reset($schedule['activity']);

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
      // Fix for no display schedule by RepeateManager.
      $schedule['startDateTime']->modify('-6 day');
      $schedule['startDateTime'] = $schedule['startDateTime']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
      $schedule['endDateTime'] = new \DateTime($schedule['endDateTime'], new \DateTimeZone('utc'));
      // Fix for no display schedule by RepeateManager.
      $schedule['endDateTime']->modify('+6 day');
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

}
