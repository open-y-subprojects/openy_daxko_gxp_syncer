<?php

namespace Drupal\openy_daxko_gxp_syncer\syncer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMappingRepository;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Class Fetcher.
 *
 * @package Drupal\openy_daxko_gxp_syncer.
 */
class SessionManager {

  /**
   * Schedules wrapper.
   *
   * @var Wrapper
   */
  protected $wrapper;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  public $entityTypeManager;

  /**
   * Mapping repository.
   *
   * @var \Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMappingRepository
   */
  public $mappingRepository;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMappingRepository $mappingRepository
   *   Mapping repository.
   */
  public function __construct(Wrapper $wrapper, EntityTypeManagerInterface $entityTypeManager, DaxkoGroupexMappingRepository $mappingRepository) {
    $this->wrapper = $wrapper;
    $this->entityTypeManager = $entityTypeManager;
    $this->mappingRepository = $mappingRepository;
    $this->logger = $this->wrapper->logger;
  }

  /**
   * Create sessions.
   */
  public function createSessions() {
    $data = $this->wrapper->getSchedulesToCreate();
    $this->logger->notice('Trying to create sessions.');
    $total = count($data);
    $current = 1;
    foreach ($data as $scheduleData) {
      $session = $this->createSession($scheduleData);
      $this->mappingRepository->create(
        $session,
        $scheduleData['locationId'],
        $scheduleData['id'],
        $scheduleData['hash']
      );
      $msg = 'Created session %id|%title with Daxko id %gxpid. Step %step from %total';
      $this->logger->debug($msg, [
        '%id' => $session->id(),
        '%title' => $scheduleData['name'],
        '%gxpid' => $scheduleData['id'],
        '%step' => $current,
        '%total' => $total,
      ]);
      $current += 1;
    }
    if (count($data) > 0) {
      $this->logger->notice('%total sessions was created.', ['%total' => count($data)]);
      return;
    }
    $this->logger->notice('Nothing to create.');
  }

  /**
   * Update sessions.
   */
  public function updateSessions() {
    $data = $this->wrapper->getSchedulesToUpdate();
    $this->logger->notice('Trying to update sessions.');
    $total = count($data);
    $current = 1;
    foreach ($data as $scheduleData) {
      $session = $this->updateSession($scheduleData);
      $msg = 'Updated session %id|%title with Daxko id %gxpid. Step %step from %total';
      $this->logger->debug($msg, [
        '%id' => $session->id(),
        '%title' => $scheduleData['name'],
        '%gxpid' => $scheduleData['id'],
        '%step' => $current,
        '%total' => $total,
      ]);
      $current += 1;
    }
    if (count($data) > 0) {
      $this->logger->notice('%total sessions was updated.', ['%total' => count($data)]);
      return;
    }
    $this->logger->notice('Nothing to update.');
  }

  /**
   * Update session.
   */
  public function updateSession($scheduleData) {
    /** @var \Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMappingInterface $mapping */
    $mapping = $this->mappingRepository->loadByGxpId($scheduleData['id']);
    $session = $mapping->session->referencedEntities();
    $session = reset($session);
    $isChange = FALSE;

    if ($session->title->value != $scheduleData['name']) {
      $session->set('title', $scheduleData['name']);
      $isChange = TRUE;
    }

    if ($session->field_session_room->value != $scheduleData['studio']) {
      $session->set('field_session_room', $scheduleData['studio']);
      $isChange = TRUE;
    }
    if ($mapping->getLocationId() != $scheduleData['locationId']) {
      $session->set('field_session_location', ['target_id' => $scheduleData['locationId']]);
      $isChange = TRUE;
    }

    if ($session->field_session_instructor->value != $scheduleData['instructor']) {
      $session->set('field_session_instructor', $scheduleData["instructor"]);
      $isChange = TRUE;
    }

    $class = $session->field_session_class->referencedEntities();
    $class = reset($class);
    if ($class->title->value != $scheduleData['name']) {
      $session->set('field_session_class', $this->getClass($scheduleData));
      $isChange = TRUE;
    }

    $sessionTime = $session->field_session_time->referencedEntities();
    $sessionTime = reset($sessionTime);
    $currentTimes = $sessionTime->field_session_time_date->getValue();
    $currentTimes = reset($currentTimes);
    if ($currentTimes['value'] != $scheduleData['startDateTime'] || $currentTimes['end_value'] != $scheduleData['endDateTime']) {
      $sessionTime->set('field_session_time_date',
        [
          'value' => $scheduleData['startDateTime'],
          'end_value' => $scheduleData['endDateTime'],
        ]
      );
      $sessionTime->save();
      $isChange = TRUE;
    }

    if ($isChange) {
      $session->save();
      $mapping->set('hash', $scheduleData['hash']);
      $mapping->save();
      return $session;
    }
    $msg = 'Can`t update session, check Daxko Grpuex API for changes. Data: %data';
    $this->logger->warning($msg, ['%data' => json_encode($scheduleData)]);
  }

  /**
   * Create session.
   */
  public function createSession($scheduleData) {
    $session = Node::create([
      'uid' => 1,
      'lang' => 'und',
      'type' => 'session',
      'title' => $scheduleData['name'],
    ]);

    if ($scheduleData['reservable']) {
      $regLink = [
        'uri' => $this->wrapper->config->get('reservation_url') . $scheduleData['reservationId'],
        'title' => $this->wrapper->config->get('reservation_text'),
      ];
      $session->set('field_session_reg_link', $regLink);
      $session->set('field_productid', $scheduleData['reservationId']);
    }

    $session->set('field_session_class', $this->getClass($scheduleData));
    $session->set('field_session_time', $this->getSessionTime($scheduleData));
    $session->set('field_session_location', ['target_id' => $scheduleData['locationId']]);
    $session->set('field_session_room', $scheduleData['studio']);
    $session->set('field_session_instructor', $scheduleData["instructor"]);

    $session->setUnpublished();

    $session->save();
    return $session;
  }

  /**
   * Create paragraphs with session time.
   */
  private function getSessionTime(array $scheduleData) {
    $days = [
      'monday',
      'tuesday',
      'wednesday',
      'thursday',
      'friday',
      'saturday',
      'sunday',
    ];
    $paragraph = Paragraph::create(['type' => 'session_time']);
    $paragraph->set('field_session_time_days', $days);
    $paragraph->set('field_session_time_date',
      [
        'value' => $scheduleData['startDateTime'],
        'end_value' => $scheduleData['endDateTime'],
      ]
    );
    $paragraph->isNew();
    $paragraph->save();

    $paragraphs[] = [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];

    return $paragraphs;
  }

  /**
   * Create class or use existing.
   *
   * @param array $class
   *   Class data.
   *
   * @return array
   *   Class references.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function getClass(array $class) {
    $nodeStorage = $this->entityTypeManager->getStorage('node');

    // Try to get existing activity.
    $existingActivities = $nodeStorage->getQuery()
      ->condition('title', $class['category'])
      ->condition('type', 'activity')
      ->condition('field_activity_category', $this->wrapper->config->get('parrent_subprogram'))
      ->execute();

    if (!$existingActivities) {
      // No activities found. Create one.
      $activity = Node::create([
        'uid' => 1,
        'lang' => 'und',
        'type' => 'activity',
        'title' => $class['category'],
        'field_activity_category' => [['target_id' => $this->wrapper->config->get('parrent_subprogram')]],
      ]);

      $activity->save();
    }
    else {
      // Use the first found existing one.
      $activityId = reset($existingActivities);
      $activity = $nodeStorage->load($activityId);
    }

    // Try to find class.
    $existingClasses = $nodeStorage->getQuery()
      ->condition('type', 'class')
      ->condition('title', $class['name'])
      ->condition('field_class_activity', $activity->id())
      ->execute();

    if (!empty($existingClasses)) {
      $classId = reset($existingClasses);
      /** @var \Drupal\node\Entity\Node $class*/
      $class = $nodeStorage->load($classId);
    }
    else {
      $paragraphs = [];
      foreach (['class_sessions', 'branches_popup_class'] as $type) {
        $paragraph = Paragraph::create(['type' => $type]);
        $paragraph->isNew();
        $paragraph->save();
        $paragraphs[] = [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ];
      }
      $class = Node::create([
        'uid' => 1,
        'lang' => 'und',
        'type' => 'class',
        'title' => $class['name'],
        'field_class_activity' => [
          [
            'target_id' => $activity->id(),
          ],
        ],
        'field_content' => $paragraphs,
      ]);

      $class->save();
    }

    return [
      'target_id' => $class->id(),
      'target_revision_id' => $class->getRevisionId(),
    ];
  }

}
