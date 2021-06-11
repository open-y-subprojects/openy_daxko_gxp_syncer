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
    if (count($data) == 0) {
      $this->logger->notice('[SESSIONMANGER] Nothing to create.');
      return;
    }
    $this->logger->notice('[SESSIONMANGER] Trying to create sessions.');
    $total = count($data);
    $current = 1;
    foreach ($data as $scheduleData) {
      $session = $this->createSession($scheduleData);
      $msg = '[SESSIONMANGER] Created session %id|%title with Daxko id %gxpid. Step %step from %total';
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
      $this->logger->notice('[SESSIONMANGER] %total sessions was created.', ['%total' => count($data)]);
      return;
    }
  }

  /**
   * Update sessions.
   */
  public function updateSessions() {
    $data = $this->wrapper->getSchedulesToUpdate();
    if (count($data) == 0) {
      $this->logger->notice('[SESSIONMANGER] Nothing to update.');
      return;
    }
    $this->logger->notice('[SESSIONMANGER] Trying to update sessions.');
    $total = count($data);
    $current = 1;
    foreach ($data as $scheduleData) {
      $session = $this->updateSession($scheduleData);
      $msg = '[SESSIONMANGER] Updated session %id|%title with Daxko id %gxpid. Step %step from %total';
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
      $this->logger->notice('[SESSIONMANGER] %total sessions was updated.', ['%total' => count($data)]);
      return;
    }
  }

  /**
   * Update session.
   */
  public function updateSession($scheduleData) {
    /** @var \Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMappingInterface $mapping */
    $mapping = $this->mappingRepository->loadByGxpId($scheduleData['id']);
    $session = $mapping->session->referencedEntities();
    $session = reset($session);
    /** @var \Drupal\Node\Entity\Node $session */
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
    $category = $class->field_class_activity->referencedEntities();
    $category = reset($category);
    if ($class->title->value != $scheduleData['activity'] || $category->title->value != $scheduleData['category']) {
      $session->set('field_session_class', $this->getClass($scheduleData));
      $isChange = TRUE;
    }

    $sessionTime = $session->field_session_time->referencedEntities();
    $sessionTime = reset($sessionTime);
    /** @var \Drupal\paragraphs\ParagraphInterface $sessionTime */
    $currentTimes = $sessionTime->field_session_time_date->getValue();
    $currentTimes = reset($currentTimes);
    $currentDay = $sessionTime->field_session_time_days->value;
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
    if ($currentDay != $scheduleData['weekDay']) {
      $sessionTime->set('field_session_time_days', [$scheduleData['weekDay']]);
      $sessionTime->save();
      $isChange = TRUE;
    }

    if ($mapping->getDay() != $scheduleData['day']) {
      $mapping->setDay($scheduleData['day']);
      $isChange = TRUE;
    }

    $availability = isset($scheduleData['availabilityStatus']) ? $scheduleData['availabilityStatus'] : NULL;
    if ($mapping->getAvailabilty() != $availability && $this->wrapper->config->get('enable_capacity_in_full_syncer')) {
      $mapping->setAvailabilty($scheduleData['availabilityStatus']);
      $isChange = TRUE;
    }

    if ($mapping->getReservable() != $scheduleData['reservable']) {
      if ($scheduleData['reservable']) {
        $mapping->isReservable();
        $regLink = [
          'uri' => $this->wrapper->config->get('reservation_url') . $scheduleData['reservationId'],
          'title' => $this->wrapper->config->get('reservation_text'),
        ];
        $session->set('field_session_reg_link', $regLink);
        $session->set('field_productid', $scheduleData['reservationId']);
      }
      else {
        $mapping->unReservable();
        $session->set('field_session_reg_link', NULL);
        $session->set('field_productid', NULL);
      }
      $isChange = TRUE;
    }

    if ($isChange) {
      $session->save();
      $mapping->set('hash', $scheduleData['hash']);
      $mapping->save();
      return $session;
    }
    $msg = '[SESSIONMANGER] Can`t update session, check Daxko Groupex API for changes and ensure that update logic for created field exist in updateSession. Data: %data';
    $this->logger->warning($msg, ['%data' => json_encode($scheduleData)]);
    return $session;
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

    $this->mappingRepository->create(
      $session,
      $scheduleData['locationId'],
      $scheduleData['id'],
      $scheduleData['hash'],
      $scheduleData['reservable'],
      $scheduleData['day'],
      isset($scheduleData['availabilityStatus']) ? $scheduleData['availabilityStatus'] : NULL,
    );
    return $session;
  }

  /**
   * Create paragraphs with session time.
   */
  private function getSessionTime(array $scheduleData) {
    $paragraphs = [];
    $paragraph = Paragraph::create(['type' => 'session_time']);
    $paragraph->set('field_session_time_days', [$scheduleData['weekDay']]);
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
      ->condition('title', $class['activity'])
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
        'title' => $class['activity'],
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
