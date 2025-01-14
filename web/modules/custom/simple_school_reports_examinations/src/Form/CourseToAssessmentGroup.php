<?php

namespace Drupal\simple_school_reports_examinations\Form;

use Drupal\backup_migrate\Core\Service\Mailer;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_examinations_support\AssessmentGroupInterface;
use Drupal\simple_school_reports_examinations_support\Entity\Examination;
use Drupal\simple_school_reports_examinations_support\Form\SetMultipleExaminationResults;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a confirmation form for mail single user.
 */
class CourseToAssessmentGroup extends ConfirmFormBase {

  protected ?NodeInterface $course = NULL;

  /**
   * Constructs a new CourseToAssessmentGroup.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'copy_to_assessment_group';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to copy @label to a new assessment group?', [
      '@label' => $this->course?->label() ?? $this->t('course')
    ]);
  }

  public function getCancelRoute() {
    return '<front>';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    if ($this->course) {
      return Url::fromRoute('entity.node.canonical', ['node' => $this->course->id()]);
    }
    return new Url($this->getCancelRoute());
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Copy');
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
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL) {
    if ($node?->bundle() !== 'course') {
      throw new AccessDeniedHttpException();
    }
    $this->course = $node;

    $form['course_nid'] = [
      '#type' => 'value',
      '#value' => $node->id(),
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
    try {
      $course_nid = $form_state->getValue('course_nid');
      $course = $course_nid ? $this->entityTypeManager->getStorage('node')->load($course_nid) : NULL;

      if (!$course) {
        $this->messenger()->addError($this->t('Something went wrong. Try again.'));
        return;
      }

      /** @var \Drupal\simple_school_reports_examinations_support\AssessmentGroupInterface $assessment_group */
      $assessment_group = $this->entityTypeManager->getStorage('ssr_assessment_group')->create([
        'label' => $course->label(),
        'langcode' => 'sv',
        'school_class' => $course->get('field_class')->target_id,
        'students' => $course->get('field_student')->getValue(),
        'subject' => $course->get('field_school_subject')->getValue(),
      ]);

      $teachers = $course->get('field_teacher')->getValue();

      foreach ($teachers as $key => $teacher) {
        $assessment_group->set('main_teacher', $teacher);
        unset($teachers[$key]);
        break;
      }

      if (!empty($teachers)) {
        $assessment_group_user = $this->entityTypeManager->getStorage('ssr_assessment_group_user')->create([
          'teachers' => $teachers,
          'administer_assessment_group' => TRUE,
          'view_examination_results' => TRUE,
          'edit_examination_results' => TRUE,
          'add_examinations' => TRUE,
        ]);
        $assessment_group->set('other_teachers', $assessment_group_user);
      }

      $violations = $assessment_group->validate();
      if (count($violations) > 0) {
        throw new \RuntimeException('Failed to copy course');
      }

      $assessment_group->save();
      $destination = Url::fromRoute('view.assessment_groups.list')->toString();
      $form_state->setRedirect('entity.ssr_assessment_group.canonical', ['ssr_assessment_group' =>  $assessment_group->id()], ['query' => ['destination' => $destination]]);
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
    }
  }

  public function access(NodeInterface $node, AccountInterface $account) {
    return AccessResult::allowedIf($node->bundle() === 'course' && $node->access('update', $account) && $account->hasPermission('create ssr_assessment_group'))->cachePerUser();
  }
}
