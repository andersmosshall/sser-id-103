<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Drupal\simple_school_reports_core\SchoolSubjectHelper;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for deactivating school subject.
 */
class DeactivateSchoolSubjectForm extends ActivateSchoolSubjectForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'deactivate_school_subject_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to deactivate school subject?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Deactivate');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * @param \Drupal\taxonomy\TermInterface $school_subject
   */
  public function setStatus(TermInterface $school_subject) {
    $school_subject->set('status', 0);
  }

}
