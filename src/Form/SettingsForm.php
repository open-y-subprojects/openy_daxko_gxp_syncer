<?php

namespace Drupal\openy_daxko_gxp_syncer\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\openy_mappings\LocationMappingRepository;

/**
 * Configure OpenY Daxko GroupEx PRO Syncer settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Mapping repository.
   *
   * @var \Drupal\ymca_mappings\LocationMappingRepository
   */
  protected $mappingRepository;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\openy_mappings\LocationMappingRepository $mappingRepository
   *   Location mapping repo.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, LocationMappingRepository $mappingRepository) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->mappingRepository = $mappingRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('openy_mappings.location_repository')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_daxko_gxp_syncer_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['openy_daxko_gxp_syncer.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openy_daxko_gxp_syncer.settings');

    $locations = $this->mappingRepository->loadAllLocationsWithGroupExId();
    // Build options list.
    $options = [];
    foreach ($locations as $location_id => $nodeType) {
      $location_id = $nodeType->toArray();
      $gxp_id = $location_id['field_groupex_id'][0]['value'];
      $options[$gxp_id] = $nodeType->label();
    }
    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => 'Daxko Client ID',
      '#default_value' => $config->get('client_id'),
      '#description' => 'This will be the username you were provided when your API credentials were generated.',
    ];
    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => 'Daxko Client Password',
      '#default_value' => $config->get('client_secret'),
      '#description' => 'This will be the password you were provided when your API credentials were generated. Your password should be securely stored and should only be required when you first generate your access token.',
    ];
    $form['scope'] = [
      '#type' => 'textfield',
      '#title' => 'ID for the customer/client',
      '#default_value' => $config->get('scope'),
      '#description' => 'This is the ID for the customer/client you are trying to programmatically interact with. Note that, while you may have access to multiple clients in your account, you will need to generate a new token for each client you are accessing.',
    ];
    $form['grant_type'] = [
      '#type' => 'textfield',
      '#title' => 'Grand type',
      '#default_value' => $config->get('grant_type'),
      '#description' => 'This will always be set to client_credentialswhen getting a new token.',
    ];
    $form['auth_url'] = [
      '#type' => 'url',
      '#title' => 'The authentication endpoint',
      '#default_value' => $config->get('auth_url'),
      '#description' => 'The authentication endpoint.',
    ];
    $form['api_url'] = [
      '#type' => 'url',
      '#title' => 'The Daxko Groupex endpoint',
      '#default_value' => $config->get('api_url'),
      '#description' => t('Base url to Daxko Groupex Api.'),
    ];
    $form['retry'] = [
      '#type' => 'textfield',
      '#title' => 'The number of attempts to get data',
      '#default_value' => $config->get('retry'),
      '#description' => 'The number of attempts to get data from daxko when daxko server return bad response.',
    ];
    $form['fetch_days'] = [
      '#type' => 'number',
      '#title' => 'Days to sync',
      '#default_value' => $config->get('fetch_days'),
      '#description' => t('Days to sync schedules.'),
    ];
    $form['availability_days'] = [
      '#type' => 'number',
      '#title' => 'Days to sync availability',
      '#default_value' => $config->get('availability_days'),
      '#description' => t('Days to sync availability for schedules.'),
    ];
    $form['delay'] = [
      '#type' => 'number',
      '#title' => 'Delay between retry get data',
      '#default_value' => $config->get('delay'),
      '#description' => t('Seconds between retry get data when daxko api returned unexpected response.'),
    ];
    $form['reservation_url'] = [
      '#type' => 'url',
      '#title' => 'Reservation URL',
      '#default_value' => $config->get('reservation_url'),
      '#description' => t('Base reservation url where users can register.'),
    ];
    $form['reservation_text'] = [
      '#type' => 'textfield',
      '#title' => 'Text on buttons for reserve schedule',
      '#default_value' => $config->get('reservation_text'),
      '#description' => t('Text on buttons for reserve schedule.'),
    ];
    $form['parrent_subprogram'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#selection_settings' => ['target_bundles' => 'activity'],
      '#title' => $this->t('Parrent SubProgram'),
      '#default_value' => Node::load($config->get('parrent_subprogram')),
      '#description' => t('What activity we should use as a parent. Should be Group Exercises under Fitness.'),
    ];
    $form['locations'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled Locations'),
      '#options' => $options,
      '#default_value' => $config->get('locations') ?: [],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $enabledLocations = array_filter($form_state->getValue('locations'));
    $result = [];
    foreach ($enabledLocations as $gxpLocationId) {
      $result[] = (int) $gxpLocationId;
    }
    $this->config('openy_daxko_gxp_syncer.settings')
      ->set('locations', $result)
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('client_secret', $form_state->getValue('client_secret'))
      ->set('scope', $form_state->getValue('scope'))
      ->set('grant_type', $form_state->getValue('grant_type'))
      ->set('auth_url', $form_state->getValue('auth_url'))
      ->set('api_url', $form_state->getValue('api_url'))
      ->set('retry', $form_state->getValue('retry'))
      ->set('fetch_days', $form_state->getValue('fetch_days'))
      ->set('availability_days', $form_state->getValue('availability_days'))
      ->set('reservation_url', $form_state->getValue('reservation_url'))
      ->set('reservation_text', $form_state->getValue('reservation_text'))
      ->set('parrent_subprogram', $form_state->getValue('parrent_subprogram'))
      ->set('delay', $form_state->getValue('delay'))
      ->save();
    $this->configFactory()->reset('openy_daxko_gxp_syncer.settings');
    parent::submitForm($form, $form_state);
  }

}
