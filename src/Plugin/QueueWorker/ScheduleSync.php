<?php

namespace Drupal\openy_daxko_gxp_syncer\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\openy_daxko_gxp_syncer\syncer\SessionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process a queue of gxp schedules for sync to database.
 *
 * @QueueWorker(
 *   id = "openy_daxko_gxp",
 *   title = @Translation("Groupex schedule syncer"),
 * )
 */
class ScheduleSync extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\openy_daxko_gxp_syncer\syncer\SessionManager
   */
  protected $sessionManager;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, SessionManager $sessionManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->sessionManager = $sessionManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('openy_daxko_gxp_syncer.session_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if ($data['action'] == 'delete') {
      /** @var \Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMappingInterface $mapping */
      $mapping = $data['mapping'];
      $session = $mapping->getSession();
      $session->delete();
      $mapping->delete();
      return;
    }
    if ($data['action'] == 'create') {
      $this->sessionManager->createSession($data);
      return;
    }
    if ($data['action'] == 'update') {
      $this->sessionManager->updateSession($data);
      return;
    }
  }

}
