<?php

namespace Drupal\openy_daxko_gxp_syncer\Plugin\QueueWorker;

use Drupal\Core\Logger\LoggerChannelInterface;
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
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  public $logger;

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
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The Loger Chanel.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelInterface $logger, SessionManager $sessionManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
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
      $container->get('logger.channel.openy_daxko_gxp_syncer'),
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
      $msg = '[GXP_DAXKO_QUEUE] Delete session %id|%title with Daxko id %gxpid. Not exist in API.';
      $this->logger->debug($msg, [
        '%id' => $session->id(),
        '%title' => $session->getTitle(),
        '%gxpid' => $mapping->getGxpId(),
      ]);
      $session->delete();
      $mapping->delete();
      return;
    }
    if ($data['action'] == 'create') {
      $session = $this->sessionManager->createSession($data);
      $msg = '[GXP_DAXKO_QUEUE] Created session %id|%title with Daxko id %gxpid.';
      $this->logger->debug($msg, [
        '%id' => $session->id(),
        '%title' => $data['name'],
        '%gxpid' => $data['id'],
      ]);
      return;
    }
    if ($data['action'] == 'update') {
      $session = $this->sessionManager->updateSession($data);
      $msg = '[GXP_DAXKO_QUEUE] Updated session %id|%title with Daxko id %gxpid.';
      $this->logger->debug($msg, [
        '%id' => $session->id(),
        '%title' => $data['name'],
        '%gxpid' => $data['id'],
      ]);
      return;
    }
  }

}
