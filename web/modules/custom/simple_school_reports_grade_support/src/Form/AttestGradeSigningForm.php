<?php

namespace Drupal\simple_school_reports_grade_support\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_school_reports_grade_support\GradeSigningInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a confirmation form to attest a grade signing document.
 */
class AttestGradeSigningForm extends AttestGradeSigningMultipleForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'attest_grade_signing_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL, $user = NULL, $ssr_grade_signing = NULL) {
    if (!$ssr_grade_signing || !$ssr_grade_signing instanceof GradeSigningInterface) {
      throw new NotFoundHttpException('no signing document');
    }

    $this->tempStoreFactory->get('attest_grade_signing')->set($this->currentUser()->id(), [$ssr_grade_signing]);
    return parent::buildForm($form, $form_state);
  }

}
