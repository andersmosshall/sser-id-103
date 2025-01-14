<?php

namespace Drupal\simple_school_reports_examinations\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_school_reports_examinations_support\AssessmentGroupInterface;
use Drupal\simple_school_reports_examinations_support\Entity\Examination;
use Drupal\simple_school_reports_examinations_support\Form\SetMultipleExaminationResults;
use Drupal\user\UserInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a confirmation form for mail single user.
 */
class SetExaminationResult extends SetMultipleExaminationResults {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'set_multiple_examinations_result';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Set examination results');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?AssessmentGroupInterface $ssr_assessment_group = NULL, ?Examination $ssr_examination = NULL, ?UserInterface $user = NULL) {
    if (!$user || !$ssr_examination || !$ssr_assessment_group) {
      throw new NotFoundHttpException('no user');
    }

    $ssr_assessment_group_ref = $ssr_examination->get('assessment_group')->entity;

    if (!$ssr_assessment_group_ref || $ssr_assessment_group_ref->id() !== $ssr_assessment_group->id()) {
      throw new AccessDeniedHttpException();
    }

    $this->tempStoreFactory->get('set_multiple_examinations_results')->set($this->currentUser()->id(), [$user]);
    return parent::buildForm($form, $form_state, $ssr_assessment_group, $ssr_examination);
  }
}
