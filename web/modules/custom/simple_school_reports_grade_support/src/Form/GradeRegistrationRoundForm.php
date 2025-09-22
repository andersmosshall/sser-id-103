<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_grade_support\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_school_reports_core\SchoolTypeHelper;
use Drupal\simple_school_reports_grade_support\Entity\GradeRegistrationCourse;
use Drupal\simple_school_reports_grade_support\GradeRegistrationCourseInterface;
use Drupal\simple_school_reports_grade_support\Service\GradableCourseServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the grade registration round entity edit forms.
 */
final class GradeRegistrationRoundForm extends ContentEntityForm {

  protected GradableCourseServiceInterface $gradableCourseService;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->gradableCourseService = $container->get('simple_school_reports_grade_support.gradable_course');
    return $instance;
  }


  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $entity = $this->getEntity();

    if ($entity->isNew()) {

      $school_types = SchoolTypeHelper::getSchoolTypeVersions(mb_strtoupper($entity->bundle()));
      $course_nid_suggestions = $this->gradableCourseService->getCourseNidsToGradeSuggestions($school_types);

      $nids_options = [];
      $courses = !empty($course_nid_suggestions)
        ? $this->entityTypeManager->getStorage('node')->loadMultiple($course_nid_suggestions)
        : [];
      /** @var \Drupal\node\NodeInterface $course */
      foreach ($courses as $course) {
        if ($course->bundle() !== 'course') {
          continue;
        }

        $label = GradeRegistrationCourse::makeLabelFromCourse($course);
        $nids_options[$course->id()] = $label;
      }

      if (!empty($nids_options)) {
        $course_weight = !empty($form['field_grade_reg_course']['#weight'])
          ? $form['field_grade_reg_course']['#weight']
          : 2;

        $form['suggested_course_wrapper'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Suggested courses to grade'),
          '#weight' => $course_weight - 0.0001,
        ];

        $form['suggested_course_wrapper']['default_courses'] = [
          '#type' => 'checkboxes',
          '#options' => $nids_options,
          '#default_value' => array_keys($nids_options),
          '#title' => $this->t('Suggested courses to grade'),
          '#description' => $this->t('The suggested courses is courses that has ended or are about to be ended as is not already added in a grade round. In addition to these you can add any course in the section below.'),
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $was_new = $this->entity->isNew();

    $result = parent::save($form, $form_state);

    if ($was_new && $this->entity->id()) {
      $entity = $this->entityTypeManager->getStorage('ssr_grade_reg_round')->load($this->entity->id());

      $new_course_set = [];

      $do_resave = FALSE;

      $added_course_ids = [];
      foreach ($entity->get('field_grade_reg_course')->referencedEntities() as $grade_reg_course) {
        if ($grade_reg_course->get('course')->target_id) {
          $new_course_set[] = $grade_reg_course;
          $added_course_ids[] = $grade_reg_course->get('course')->target_id;
        }
        else {
          $do_resave = TRUE;
        }
      }

      foreach ($form_state->getValue('default_courses', []) as $course_nid => $checked) {
        if ($checked && !in_array($course_nid, $added_course_ids)) {
          $grade_reg_course = $this->entityTypeManager->getStorage('ssr_grade_reg_course')->create([
            'bundle' => mb_strtolower($entity->bundle()),
            'langcode' => $entity->language()->getId(),
            'course' => ['target_id' => $course_nid],
            'registration_status' => GradeRegistrationCourseInterface::REGISTRATION_STATUS_NOT_STARTED,
          ]);
          $grade_reg_course->save();
          $new_course_set[] = $grade_reg_course;
          $do_resave = TRUE;
        }
      }

      if ($do_resave) {
        $new_set_targets = [];
        foreach ($new_course_set as $course_entity) {
          $new_set_targets[] = ['target_id' => $course_entity->id()];
        }
        $entity->set('field_grade_reg_course', $new_set_targets);
        $entity->save();
      }
    }

    $message_args = ['%label' => $this->entity->toLink()->toString()];
    $logger_args = [
      '%label' => $this->entity->label(),
      'link' => $this->entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New grade registration round %label has been created.', $message_args));
        $this->logger('simple_school_reports_grade_support')->notice('New grade registration round %label has been created.', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The grade registration round %label has been updated.', $message_args));
        $this->logger('simple_school_reports_grade_support')->notice('The grade registration round %label has been updated.', $logger_args);
        break;

      default:
        throw new \LogicException('Could not save the entity.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl());

    return $result;
  }

}
