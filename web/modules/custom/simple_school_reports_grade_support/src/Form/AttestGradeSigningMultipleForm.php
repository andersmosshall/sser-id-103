<?php

namespace Drupal\simple_school_reports_grade_support\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Drupal\simple_school_reports_core\Form\ConfirmWithSigningFormBase;
use Drupal\simple_school_reports_core\Service\EmailService;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;
use Drupal\simple_school_reports_entities\SsrSigningInterface;
use Drupal\simple_school_reports_maillog\SsrMaillogInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a confirmation form to attest grade signing documents.
 */
class AttestGradeSigningMultipleForm extends ConfirmWithSigningFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'attest_grade_signings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Attest signing documents');
  }

  public function getCancelRoute() {
    return 'simple_school_reports_grade_support.grade_registration_types';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url($this->getCancelRoute());
  }

  /**
   * {@inheritdoc}
   */
  protected function beforeSigningBuildForm($form, $form_state): array {

    $signing_documents = $this->tempStoreFactory
      ->get('attest_grade_signing')
      ->get($this->currentUser()->id());
    if (empty($signing_documents)) {
      throw new NotFoundHttpException();
    }

    $document_summaries = [];

    $form['signing_document_ids'] = ['#tree' => TRUE];
    /** @var \Drupal\simple_school_reports_grade_support\GradeSigningInterface $signing_document */
    foreach ($signing_documents as $signing_document) {
      $id = $signing_document->id();

      if ($signing_document->get('signing_complete')->value) {
        continue;
      }

      $document_summaries[$id] = $signing_document->getShortSummary();
      $form['signing_document_ids'][$id] = [
        '#type' => 'value',
        '#value' => $id,
      ];
    }

    $form['intro'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Attest the following signing documents'),
    ];

    $form['document_summaries'] = [
      '#theme' => 'item_list',
      '#items' => $document_summaries,
    ];

    if (empty($document_summaries)) {
      throw new AccessDeniedHttpException();
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function makeSignTemplate(array $safe_form_values, FormStateInterface $form_state): string {
    $signing_document_ids = array_values($safe_form_values['signing_document_ids'] ?? []);

    $signing_documents = !empty($signing_document_ids)
      ? $this->entityTypeManager->getStorage('ssr_grade_signing')->loadMultiple($signing_document_ids)
      : [];

    if (empty($signing_documents)) {
      throw new AccessDeniedHttpException('No signing document ids provided.');
    }

    $text = '<p>' . $this->t('I confirm that the following documents have been signed and, if applicable, archived according to the school policy:');
    /** @var \Drupal\simple_school_reports_grade_support\GradeSigningInterface $signing_document */
    foreach ($signing_documents as $signing_document) {
      $text .= '<br/>' . '* ' . $signing_document->getShortSummary();
    }

    return $text . '</p>';
  }

  /**
   * {@inheritdoc}
   */
  protected function afterSigningSubmit(array &$form, FormStateInterface $form_state, array $safe_form_values, SsrSigningInterface $signing) {
    $signing_document_ids = array_values($safe_form_values['signing_document_ids'] ?? []);

    $signing_documents = !empty($signing_document_ids)
      ? $this->entityTypeManager->getStorage('ssr_grade_signing')->loadMultiple($signing_document_ids)
      : [];

    if (empty($signing_documents)) {
      throw new AccessDeniedHttpException('No signing document ids provided.');
    }

    foreach ($signing_documents as $signing_document) {
      $signing_document->set('signing', $signing);
      $signing_document->save();
    }

    $this->messenger()->addStatus($this->t('Signing documents attested'));
  }

}
