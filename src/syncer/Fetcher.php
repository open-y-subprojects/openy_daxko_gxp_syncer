<?php

namespace Drupal\openy_daxko_gxp_syncer\syncer;

use Drupal\openy_daxko_gxp_syncer\DaxkoGxpClient;

/**
 * Fetch data from Daxko groupex api.
 *
 * @package Drupal\openy_daxko_gxp_syncer.
 */
class Fetcher {

  /**
   * Config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Client.
   *
   * @var \Drupal\openy_daxko_gxp_syncer\DaxkoGxpClient
   */
  protected $client;

  /**
   * Schedules wrapper.
   *
   * @var Wrapper
   */
  protected $wrapper;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructor.
   *
   * @param \Drupal\openy_daxko_gxp_syncer\DaxkoGxpClient $client
   *   Client.
   * @param Wrapper $wrapper
   *   Wrapper.
   */
  public function __construct(DaxkoGxpClient $client, Wrapper $wrapper) {
    $this->client = $client;
    $this->wrapper = $wrapper;
    $this->config = $this->wrapper->config;
    $this->logger = $this->wrapper->logger;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch() {
    $begin = new \DateTime('now');
    $begin->setTimezone(new \DateTimeZone(date_default_timezone_get()));

    $end = clone $begin;
    $fetchDays = $this->config->get('fetch_days');
    $end->modify('+' . $fetchDays . ' day');

    // Get capacity from extended url query parameter.
    $enableCapacity = $this->config->get('enable_capacity_in_full_syncer');

    $this->logger->notice('[FETCHER] Fetch data from daxko groupex api. Begin date: %start, End date: %end', [
      '%start' => $begin->format('Y-m-d'),
      '%end' => $end->format('Y-m-d'),
    ]);
    $locations = $this->config->get('locations');
    $locationsMapping = $this->wrapper->getLocationMapping();

    $data = [];
    /** @var \DateTime $date */
    foreach ($locations as $locationid) {
      $msg = '[FETCHER] Trying to get data for location %title with groupex id %id';
      $this->logger->debug($msg, [
        '%title' => $locationsMapping[$locationid]['title'],
        '%id' => $locationid,
      ]);
      $gxpData = $this->client->getSchedules(
        $begin->format('Y-m-d'),
        $end->format('Y-m-d'),
        $locationid,
        $enableCapacity
      );
      $msg = '[FETCHER] Location %title with groupex id %id has %count schedules.';

      $this->logger->debug($msg, [
        '%title' => $locationsMapping[$locationid]['title'],
        '%id' => $locationid,
        '%count' => count($gxpData),
      ]);
      if (!empty($gxpData)) {
        $data = array_merge($data, $gxpData);
      }
    }
    $this->logger->debug('[FETCHER] There are %total schedules from api for processing', [
      '%total' => count($data),
    ]);
    $this->wrapper->addSchedules($data);
  }

}
