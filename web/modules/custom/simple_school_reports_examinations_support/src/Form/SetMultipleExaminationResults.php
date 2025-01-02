<?php

namespace Drupal\simple_school_reports_examinations_support\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_examinations_support\AssessmentGroupInterface;
use Drupal\simple_school_reports_examinations_support\Entity\Examination;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for set multiple examinations results.
 */
class SetMultipleExaminationResults extends ConfirmFormBase {

  /**
   * Constructs a new SetMultipleExaminationResults.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   The temp store factory.
   */
  public function __construct(
    protected PrivateTempStoreFactory $tempStoreFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'set_multiple_examinations_results';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Set examination results');
  }

  public function getCancelRoute() {
    return '<front>';
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
  public function getConfirmText() {
    return $this->t('Save');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?AssessmentGroupInterface $ssr_assessment_group = NULL, ?Examination $ssr_examination = NULL) {
    /** @var \Drupal\simple_school_reports_examinations_support\AssessmentGroupInterface|null $examination_assessment_group */
    $examination_assessment_group = $ssr_examination?->get('assessment_group')->entity;
    if (
      !$ssr_examination ||
      !$ssr_assessment_group ||
      $ssr_assessment_group->id() !== $examination_assessment_group?->id() ||
      !$ssr_assessment_group->access('handle_all_results')
    ) {
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
      return $this->redirect($this->getCancelRoute());
    }

    $form['examination_id'] = [
      '#type' => 'value',
      '#value' => $ssr_examination->id(),
    ];

    // Retrieve the accounts to be canceled from the temp store.
    /** @var \Drupal\user\Entity\User[] $accounts */
    $accounts = $this->tempStoreFactory
      ->get('set_multiple_examinations_results')
      ->get($this->currentUser()->id());
    if (empty($accounts)) {
      return $this->redirect($this->getCancelRoute());
    }

    if (empty(assessment_group_user_examination_result_state_options())) {
      throw new AccessDeniedHttpException();
    }

    $names = [];

    $form['accounts'] = ['#tree' => TRUE];
    $uids = [];
    foreach ($accounts as $account) {
      $uid = $account->id();
      if ($uid <= 1) {
        continue;
      }

      $uids[] = $uid;

      $names[$uid] = $account->getDisplayName();
      $form['accounts'][$uid] = [
        '#type' => 'value',
        '#value' => $uid,
      ];
    }

    $form['account']['names'] = [
      '#theme' => 'item_list',
      '#items' => $names,
    ];

    if (empty($names)) {
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
      return $this->redirect($this->getCancelRoute());
    }

    $examination_result = NULL;
    if (count($uids) === 1) {
      $uid = $uids[0];
      $examination_result = current($this->entityTypeManager->getStorage('ssr_examination_result')->loadByProperties([
        'student' => $uid,
        'examination' => $ssr_examination->id(),
      ]));
    }

    $form['examination_state'] = [
      '#type' => 'select',
      '#title' => $this->t('State', [], ['context' => 'ssr']),
      '#options' => assessment_group_user_examination_result_state_options(),
      '#default_value' => $examination_result ? $examination_result->get('state')->value : '',
      '#empty_option' => $this->t('- Select -'),
      '#required' => TRUE,
    ];

    $state_not_applicable = Settings::get('ssr_examination_result_not_applicable', 'no_value');

    $form['examination_status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Published'),
      '#description' => $this->t('If not published this examination result will be shown on the student tab.'),
      '#default_value' => $examination_result ? $examination_result->get('status')->value : TRUE,
      '#access' => !!$ssr_examination->get('status')->value,
      '#states' => [
        'invisible' => [
          ':input[name="examination_state"]' => ['value' => $state_not_applicable],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getValue('confirm')) {
      $this->logger('confirm_form')->error('Confirm issue!');
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
      $form_state->setRebuild(TRUE);
      return;
    }

    $examination_state = $form_state->getValue('examination_state');
    $examination_status = $form_state->getValue('examination_status');
    $examination_id = $form_state->getValue('examination_id');

    if (empty($examination_state) || $examination_status === NULL) {
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
      $form_state->setRebuild(TRUE);
      return;
    }

    // Initialize batch (to set title).
    $batch = [
      'title' => $this->t('Saving examination results'),
      'init_message' => $this->t('Saving examination results'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'operations' => [],
      'finished' => [self::class, 'finished'],
    ];

    foreach ($form_state->getValue('accounts') as $uid => $value) {
      $batch['operations'][] = [[self::class, 'batchSaveResult'], [$uid, $examination_id, $examination_state, $examination_status]];
    }

    if (!empty($batch['operations'])) {
      if (count($batch['operations']) < 10) {
        $batch['progressive'] = FALSE;
      }
      batch_set($batch);
    }
    else {
      $this->messenger()->addStatus($this->t('@count examination results saved.', ['@count' => 0]));
    }
  }

  public static function batchSaveResult($uid, $examination_id, $examination_state, $examination_status, &$context) {
    try {
      $examination_result_storage = \Drupal::entityTypeManager()->getStorage('ssr_examination_result');
      $examination_result = current($examination_result_storage->loadByProperties([
        'student' => $uid,
        'examination' => $examination_id,
      ]));

      $state_not_applicable = Settings::get('ssr_examination_result_not_applicable');
      if (!$examination_result && $examination_state !== $state_not_applicable) {
        $examination_result = $examination_result_storage->create([
          'student' => ['target_id' => $uid],
          'examination' => ['target_id' => $examination_id],
          'langcode' => 'sv',
        ]);
      }
      if ($examination_state === $state_not_applicable) {
        $examination_result?->delete();
        $context['results']['saved'][] = $uid;
        return;
      }
      $examination_result->set('state', $examination_state);
      $examination_result->set('status', $examination_status);
      $examination_result->save();
      $context['results']['saved'][] = $uid;
    }
    catch (\Exception $e) {
      $context['results']['failed'][] = $uid;
      return;
    }
  }

  public static function finished($success, $results) {
    if (!$success || empty($results['saved'])) {
      \Drupal::messenger()->addError(t('Something went wrong'));
      return;
    }

    \Drupal::messenger()->addStatus(t('@count examination results saved.', ['@count'  => count($results['saved'])]));
  }
}
