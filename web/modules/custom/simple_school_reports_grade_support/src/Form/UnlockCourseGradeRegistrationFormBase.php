<?php

namespace Drupal\simple_school_reports_grade_support\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\SchoolTypeHelper;
use Drupal\simple_school_reports_grade_support\GradeRegistrationCourseInterface;
use Drupal\simple_school_reports_grade_support\Service\GradableCourseServiceInterface;
use Drupal\simple_school_reports_grade_support\Service\GradeService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a form base for unlock course grade registration.
 */
abstract class UnlockCourseGradeRegistrationFormBase extends ConfirmFormBase {

  /**
   * @var \Drupal\node\NodeInterface|null
   */
  protected ?NodeInterface $course = NULL;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  protected GradableCourseServiceInterface $gradableCourseService;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->connection = $container->get('database');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->gradableCourseService = $container->get('simple_school_reports_grade_support.gradable_course');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to unlock course @name for grade registration?', [ '@name' => $this->course?->label() ?? '' ]);
  }

  public function getDescription() {
    return '';
  }

  public function getConfirmText() {
    return $this->t('Unlock');
  }

  /**
   * @return string
   */
  abstract public function getCancelRoute(): string;

  abstract public function getSchoolTypeVersions(): array;

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url($this->getCancelRoute());
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    ?NodeInterface $node = NULL,
  ) {
    if (!$node || $node->bundle() !== 'course') {
      throw new NotFoundHttpException();
    }
    $course = $node;
    $this->course = $node;

    if (!$this->gradableCourseService->allowUnlockGradeRegistration($course)) {
      throw new AccessDeniedHttpException();
    }

    $form['course_id'] = [
      '#type' => 'value',
      '#value' => $course->id(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect($this->getCancelRoute());

    $course_id = $form_state->getValue('course_id');

    $query = $this->connection->select('ssr_grade_reg_course', 'rc');
    $query->innerJoin('ssr_grade_reg_round__field_grade_reg_course', 'r', 'r.field_grade_reg_course_target_id = rc.id');
    $query->innerJoin('ssr_grade_reg_round_field_data', 'rd', 'r.entity_id = rd.id');
    $query->condition('rc.course', $course_id);
    $query->condition('rc.registration_status', GradeRegistrationCourseInterface::REGISTRATION_STATUS_DONE);
    $query->condition('rd.open', TRUE);
    $query->fields('rc', ['id']);
    $results = $query->execute();

    $grade_reg_course_ids = [];
    foreach ($results as $result) {
      $grade_reg_course_ids[] = $result->id;
    }

    if (empty($grade_reg_course_ids)) {
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
      return;
    }

    $grade_reg_courses = $this->entityTypeManager->getStorage('ssr_grade_reg_course')->loadMultiple($grade_reg_course_ids);
    foreach ($grade_reg_courses as $grade_reg_course) {
      $grade_reg_course->set('registration_status', GradeRegistrationCourseInterface::REGISTRATION_STATUS_STARTED);
      $grade_reg_course->save();
    }
    $this->messenger()->addStatus($this->t('The grade registration is unlocked.'));
  }

  /**
   * Access check for the form.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   * @param \Drupal\node\NodeInterface|NULL $node
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   */
  public function access(?AccountInterface $account = NULL, NodeInterface $node = NULL): AccessResultInterface {
    if (!$account) {
      $account = $this->currentUser();
    }
    $course = $node;
    if (!$course || $course->bundle() !== 'course') {
      return AccessResult::forbidden()->addCacheContexts(['route']);
    }

    $school_type = SchoolTypeHelper::getSchoolTypeFromSchoolTypeVersioned($this->getSchoolTypeVersions()[0] ?? '');
    return $course->access('unlock_register_course_grades_' . (mb_strtolower($school_type)), $account, TRUE);
  }

}
