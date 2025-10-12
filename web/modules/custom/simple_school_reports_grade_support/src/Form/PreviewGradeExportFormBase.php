<?php

namespace Drupal\simple_school_reports_grade_support\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\simple_school_reports_grade_support\Service\GradableCourseServiceInterface;
use Drupal\simple_school_reports_grade_support\Service\GradeServiceInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a form base for preview grades before export.
 */
abstract class PreviewGradeExportFormBase extends ConfirmFormBase {

  protected GradeServiceInterface $gradeService;

  protected GradableCourseServiceInterface $gradableCourseService;

  protected EntityTypeManagerInterface $entityTypeManager;

  protected BlockManagerInterface $blockManager;


  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->gradeService = $container->get('simple_school_reports_grade_support.grade_service');
    $instance->gradableCourseService = $container->get('simple_school_reports_grade_support.gradable_course');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->blockManager = $container->get('plugin.manager.block');
    $instance->requestStack = $container->get('request_stack');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Preview grades');
  }

  public function getDescription() {
    return '';
  }

  public function getConfirmText() {
    return $this->t('Update');
  }

  /**
   * @return string
   */
  abstract public function getCancelRoute(): string;

  abstract public function getSchoolTypeVersions(): array;

  protected function getSyllabusIds(): array {
    return $this->gradableCourseService->getGradableSyllabusIds($this->getSchoolTypeVersions());
  }

  abstract protected function getPreviewBlockId(): string;

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
  ) {

    $syllabus_ids = $this->getSyllabusIds();

    $student_ids = $this->gradeService->getStudentIdsWithGrades($syllabus_ids);
    if (empty($student_ids)) {
      throw new NotFoundHttpException();
    }

    $student_options = [];
    $users = $this->entityTypeManager->getStorage('user')->loadMultiple($student_ids);
    /** @var \Drupal\user\UserInterface $user */
    foreach ($users as $user) {
      $student_options[$user->id()] = $user->getDisplayName();
    }
    if (empty($student_options)) {
      throw new AccessDeniedHttpException();
    }

    $student_ids_string = $this->requestStack->getCurrentRequest()->query->get('student_ids', '');
    $student_ids = is_string($student_ids_string) ? explode(',', $student_ids_string) : [];

    $default_students = $student_ids;
    $form['student_ids'] = [
      '#type' => 'ssr_multi_select',
      '#title' => $this->t('Students'),
      '#description' => $this->t('Select the list of students to preview grades for.'),
      '#options' => $student_options,
      '#default_value' => $default_students,
      '#filter_placeholder' => $this->t('Enter name or grade/class to filter'),
      '#required' => FALSE,
    ];

    $request_method = $this->requestStack->getCurrentRequest()->getMethod();
    $form = parent::buildForm($form, $form_state);


    $form['actions']['#type'] = 'container';
    $form['actions']['#attributes']['class'] = ['form-actions'];


    if ($request_method === 'GET' && !empty($student_ids)) {
      $students = $this->entityTypeManager->getStorage('user')->loadMultiple($student_ids);
      if (!empty($students)) {
        foreach (array_values($students) as $key => $student) {
          $form[$key . '_preview'] = $this->buildStudentPreview($student);

          if ($key < count($students) - 1) {
            $form[$key . '_divider'] = [
              '#markup' => '<hr>',
            ];
          }
        }
      }
    }

    return $form;
  }

  protected function buildStudentPreview(UserInterface $student): array {
    $build = [];

    $build['no_grades'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $student->getDisplayName(),
    ];

    $block = $this->blockManager->createInstance($this->getPreviewBlockId());
    if (!$block instanceof BlockBase) {
      $build['no_grades'] = [
        '#type' => 'html_tag',
        '#tag' => 'em',
        '#value' => $this->t('No grades to show'),
      ];
      return $build;
    }


    $build['stats'] = $block->build($student);
    return $build;


    $total_points = 0;

    // TODO MOVE TO STATS BLOCK.
    $build['total_points'] = [
      '#theme' => 'field',
      '#title' => t('Written reviews'),
      '#label_display' => 'above',
      '#view_mode' => 'default',
      '#field_name' => 'total_points',
      '#field_type' => 'text',
      '#field_translatable' => FALSE,
      '#entity_type' => $student->getEntityTypeId(),
      '#bundle' => $student->bundle(),
      '#object' => $student,
      '#is_multiple' => FALSE,
      '#items' => [],
      0 => [
        '#plain_text' => $total_points,
      ],
    ];




    if (empty($total_points)) {
      unset($build['total_points']);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_route_name = $this->getRouteMatch()->getRouteName();
    $form_state->setRedirect($current_route_name, [], ['query' => ['student_ids' => implode(',', $form_state->getValue('student_ids'))]]);
  }

  /**
   * Access check for the form.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   */
  public function access(?AccountInterface $account = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermission($account, 'administer simple school reports settings');
  }

}
