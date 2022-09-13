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
    $form['description'] = [
      '#markup' => '<p>' . $this->t('This form provides configuration of the authentication credentials for latest GroupEx PRO API and provides settings for availability and reservation options for the classes.') . '<p>',
    ];
    $form['api_doc_link'] = [
      '#markup' => '<a href="https://docs.partners.daxko.com/openapi/gxp/v1/">' . $this->t('Daxko GroupEx Pro API documentation.') . '</a>',
    ];
    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => 'Daxko Client ID',
      '#default_value' => $config->get('client_id'),
      '#description' => $this->t('Your Groupex Pro API credentials provided by Daxko.'),
    ];
    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => 'Daxko Client Password',
      '#default_value' => $config->get('client_secret'),
      '#description' => $this->t('Your Groupex Pro API client password provided by Daxko.'),
    ];
    $form['scope'] = [
      '#type' => 'textfield',
      '#title' => 'ID for the customer/client',
      '#default_value' => $config->get('scope'),
      '#description' => $this->t('Your GroupEx PRO Client ID. Additional clien IDs require separate token to be generated.'),
    ];
    $form['auth_url'] = [
      '#type' => 'url',
      '#title' => 'The authentication endpoint',
      '#default_value' => $config->get('auth_url'),
      '#description' => $this->t('The authentication endpoint.'),
    ];
    $form['api_url'] = [
      '#type' => 'url',
      '#title' => 'The Daxko Groupex endpoint',
      '#default_value' => $config->get('api_url'),
      '#description' => $this->t('Base URL to Daxko Groupex Api.'),
    ];
    $form['retry'] = [
      '#type' => 'textfield',
      '#title' => 'The number of attempts to get data',
      '#default_value' => $config->get('retry'),
      '#description' => $this->t('The number of attempts to get data from daxko when daxko server return bad response.'),
    ];
    $form['fetch_days'] = [
      '#type' => 'number',
      '#title' => 'Days to sync',
      '#default_value' => $config->get('fetch_days'),
      '#description' => $this->t('Sync classes for set number of days ahead.'),
    ];
    $form['availability_days'] = [
      '#type' => 'number',
      '#title' => 'Days to sync availability',
      '#default_value' => $config->get('availability_days'),
      '#description' => $this->t('Request available spots for classes for set number of days ahead.'),
    ];
    $form['delay'] = [
      '#type' => 'number',
      '#title' => 'Delay between retry get data',
      '#default_value' => $config->get('delay'),
      '#max' => 60,
      '#min' => 0,
      '#description' => $this->t('Seconds between retry get data when daxko api returned unexpected response. 0 - no delay, max 60 seconds'),
    ];
    $form['timeout'] = [
      '#type' => 'number',
      '#title' => 'Timeout between save schedule to database',
      '#default_value' => $config->get('delay'),
      '#max' => 10000,
      '#min' => 0,
      '#description' => $this->t('Miliseconds between save node. 0 - no delay, 1000 - 1 second, max 10 seconds'),
    ];
    $form['reservation_url'] = [
      '#type' => 'url',
      '#title' => 'Reservation URL',
      '#default_value' => $config->get('reservation_url'),
      '#description' => $this->t('Base reservation url where users can register.'),
    ];
    $form['reservation_text'] = [
      '#type' => 'textfield',
      '#title' => 'Text on buttons for reserve schedule',
      '#default_value' => $config->get('reservation_text'),
      '#description' => $this->t('Text on buttons for reserve a spot.'),
    ];
    $form['parrent_subprogram'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#selection_settings' => ['target_bundles' => 'activity'],
      '#title' => $this->t('Parrent SubProgram'),
      '#default_value' => Node::load($config->get('parrent_subprogram')),
      '#description' => $this->t('Parent Program Subcategory (PEF) for GXP classes.'),
    ];
    $form['locations'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled Locations'),
      '#options' => $options,
      '#description' => $this->t('The API requests will run for selected locations and they will be disaplyed in The Schedules interface'),
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
      ->set('auth_url', $form_state->getValue('auth_url'))
      ->set('api_url', $form_state->getValue('api_url'))
      ->set('retry', $form_state->getValue('retry'))
      ->set('fetch_days', $form_state->getValue('fetch_days'))
      ->set('availability_days', $form_state->getValue('availability_days'))
      ->set('reservation_url', $form_state->getValue('reservation_url'))
      ->set('reservation_text', $form_state->getValue('reservation_text'))
      ->set('parrent_subprogram', $form_state->getValue('parrent_subprogram'))
      ->set('delay', $form_state->getValue('delay'))
      ->set('timeout', $form_state->getValue('timeout'))
      ->save();
    $this->configFactory()->reset('openy_daxko_gxp_syncer.settings');
    parent::submitForm($form, $form_state);
  }

}
