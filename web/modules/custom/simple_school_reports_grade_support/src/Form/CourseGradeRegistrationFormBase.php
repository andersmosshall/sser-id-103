<?php

namespace Drupal\simple_school_reports_grade_support\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_grade_support\Service\GradableCourseServiceInterface;
use Drupal\simple_school_reports_grade_support\Service\GradeService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a form base for course grade registration.
 */
abstract class CourseGradeRegistrationFormBase extends GradeRegistrationFormBase {

  /**
   * @var \Drupal\node\NodeInterface|null
   */
  protected ?NodeInterface $course = NULL;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL) {
    if (!$node || $node->bundle() !== 'course') {
      throw new NotFoundHttpException();
    }
    $course = $node;
    $this->course = $node;

    $form['course_nid'] = [
      '#type' => 'value',
      '#value' => $course->id(),
    ];

    return parent::buildForm($form, $form_state);
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
    return $course->access('register_course_grades', $account, TRUE);
  }

}
