<?php

namespace Drupal\simple_school_reports_core_gy\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\FileInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Interface for CourseDataServiceGr.
 */
interface CourseDataServiceGyInterface {

  /**
   * @return array
   *   An associative array containing course data keyed by course code.
   *   Each course data entry has the following fields:
   *   Course,Course code,Subject,Subject code,Link
   *    - 'label': The label of the course.
   *    - 'course_code': The course code.
   *    - 'subject_code': The subject code.
   *    - 'subject_name': The label of the subject.
   *    - 'link': External link for detailed course info.
   *    - 'points': The points of with the course if applicable.
   *    - 'use_langcode': If the course is associated with a language.
   *    - 'official': Whether the course is official or not.
   *    - 'group_for': The course codes this cours is a group for, if applicable.
   *    - 'levels': The course codes this course is levels for, if applicable.
   *    - 'grade_vid': The vocabulary ID for the grade taxonomy term, or 'none'.
   *    - 'points': The points for the course.
   *    - 'school_type_versioned': School type version, e.g. GR:22.
   *
 */
  public function getCourseData(): array;

  /**
   * @return array
   *   Subject data is keyed by subject code, and each value is an associative
   *   array with the following keys:
   *   - 'subject_code': The subject code.
   *   - 'subject_name': The label of the subject.
   */
  public function getSubjectsData(): array;

}
