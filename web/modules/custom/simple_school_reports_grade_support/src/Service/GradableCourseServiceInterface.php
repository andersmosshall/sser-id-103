<?php

namespace Drupal\simple_school_reports_grade_support\Service;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Provides an interface defining GradableCourseService.
 */
interface GradableCourseServiceInterface {

  /**
   * @param array $school_types
   *
   * @return array
   */
  public function getGradableSyllabusIds(array $school_types): array;

  /**
   * @param \Drupal\node\NodeInterface $course
   *
   * @return bool
   */
  public function courseIsGradable(NodeInterface $course): bool;

  /**
   * A list of nids suggested to be included in a grade round.
   *
   * That is courses that has ended or ending within next 60 days and is not
   * already in a grade round.
   *
   * @param array $school_types
   *
   * @return string[]
   */
  public function getCourseNidsToGradeSuggestions(array $school_types): array;

  /**
   * @param \Drupal\node\NodeInterface $course
   * @param \Drupal\user\UserInterface|null $account
   *
   * @return bool
   */
  public function allowGradeRegistration(NodeInterface $course, ?AccountInterface $account = NULL): bool;

  /**
   * @param \Drupal\node\NodeInterface $course
   * @param \Drupal\user\UserInterface|null $account
   *
   * @return bool
   */
  public function allowViewGrades(NodeInterface $course, ?AccountInterface $account = NULL): bool;

}
