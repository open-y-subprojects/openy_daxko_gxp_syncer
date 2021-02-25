<?php

namespace Drupal\openy_daxko_gxp_syncer\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMappingInterface;

/**
 * Defines the daxko groupex mapping entity class.
 *
 * @ContentEntityType(
 *   id = "daxko_groupex_mapping",
 *   label = @Translation("Daxko GroupEx Mapping"),
 *   label_collection = @Translation("Daxko GroupEx Mappings"),
 *   handlers = {
 *     "view_builder" = "Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMappingViewBuilder",
 *     "list_builder" = "Drupal\openy_daxko_gxp_syncer\DaxkoGroupexMappingListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\openy_daxko_gxp_syncer\Form\DaxkoGroupexMappingForm",
 *       "edit" = "Drupal\openy_daxko_gxp_syncer\Form\DaxkoGroupexMappingForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "daxko_groupex_mapping",
 *   admin_permission = "access daxko groupex mapping overview",
 *   entity_keys = {
 *     "id" = "id",
 *     "gxpid" = "gxpid",
 *     "session" = "session",
 *     "locationid" = "locationid",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/content/daxko-groupex-mapping/add",
 *     "canonical" = "/daxko_groupex_mapping/{daxko_groupex_mapping}",
 *     "edit-form" = "/admin/content/daxko-groupex-mapping/{daxko_groupex_mapping}/edit",
 *     "delete-form" = "/admin/content/daxko-groupex-mapping/{daxko_groupex_mapping}/delete",
 *     "collection" = "/admin/content/daxko-groupex-mapping"
 *   },
 * )
 */
class DaxkoGroupexMapping extends ContentEntityBase implements DaxkoGroupexMappingInterface {

  /**
   * {@inheritdoc}
   */
  public function getGxpId() {
    return $this->get('gxpid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setGxpId($gxpId) {
    $this->set('gxpid', $gxpId);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHash() {
    return $this->get('hash')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setHash($hash) {
    $this->set('hash', $hash);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocationId() {
    return $this->get('locationid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocationId($locationId) {
    $this->set('locationid', $locationId);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDay() {
    return $this->get('day')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDay($day) {
    $this->set('day', $day);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailabilty() {
    return $this->get('availabilty')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAvailabilty($text) {
    $this->set('availabilty', $text);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getReservable() {
    return $this->get('reservable')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isReservable() {
    $this->set('reservable', TRUE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unReservable() {
    $this->set('reservable', FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSession() {
    $session = $this->session->referencedEntities();
    $session = reset($session);
    return $session;
  }

  /**
   * {@inheritdoc}
   */
  public function setSession($session) {
    $this->set('session', $session);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['session'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Session'))
      ->setDescription(t('Reference to the Session.'))
      ->setSetting('target_type', 'node')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
      ]);

    $fields['gxpid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Groupex ID'))
      ->setDescription(t('Used to map source groupex ID.'))
      ->setRequired(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 32,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['locationid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Location ID'))
      ->setDescription(t('Used to map source Location ID.'))
      ->setRequired(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 32,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['day'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Day'))
      ->setDescription(t('Day when display schedule'))
      ->setRequired(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 32,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['availabilty'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Availabilty'))
      ->setDescription(t('Availabilty status for schedule'))
      ->setRequired(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 32,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['reservable'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Reservable'))
      ->setDescription(t('Flag for reservable flag'))
      ->setInitialValue(FALSE)
      ->setDefaultValue(FALSE);

    $fields['hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hash'))
      ->setDescription(t('Used to map source data hash.'))
      ->setRequired(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
