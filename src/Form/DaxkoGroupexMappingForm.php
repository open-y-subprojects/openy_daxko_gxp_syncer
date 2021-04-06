<?php

namespace Drupal\openy_daxko_gxp_syncer\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the daxko groupex mapping entity edit forms.
 */
class DaxkoGroupexMappingForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => render($link)];

    if ($result == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New daxko groupex mapping %label has been created.', $message_arguments));
      $this->logger('openy_daxko_gxp_syncer')->notice('Created new daxko groupex mapping %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The daxko groupex mapping %label has been updated.', $message_arguments));
      $this->logger('openy_daxko_gxp_syncer')->notice('Updated new daxko groupex mapping %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.daxko_groupex_mapping.canonical', ['daxko_groupex_mapping' => $entity->id()]);
  }

}
