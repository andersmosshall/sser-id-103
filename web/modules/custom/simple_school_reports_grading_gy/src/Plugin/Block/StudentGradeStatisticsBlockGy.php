<?php

namespace Drupal\simple_school_reports_grading_gy\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_school_reports_core\SchoolGradeHelper;
use Drupal\simple_school_reports_core\SchoolTypeHelper;
use Drupal\simple_school_reports_entities\Service\SyllabusServiceInterface;
use Drupal\simple_school_reports_grade_support\GradeSnapshotInterface;
use Drupal\simple_school_reports_grade_support\Plugin\Block\StudentGradeStatisticsBlockBase;
use Drupal\simple_school_reports_grade_support\Service\GradableCourseServiceInterface;
use Drupal\simple_school_reports_grade_support\Service\GradeServiceInterface;
use Drupal\simple_school_reports_grade_support\Service\GradeSnapshotServiceInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\simple_school_reports_grade_support\Utilities\GradeReference;

/**
 * Provides a 'StudentGradeStatisticsBlockGy' block.
 *
 * @Block(
 *  id = "student_grade_statistics_block_gy",
 *  admin_label = @Translation("Student grade statistics GY"),
 * )
 */
class StudentGradeStatisticsBlockGy extends StudentGradeStatisticsBlockBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * @return array
   */
  protected function getSyllabusIds(): array {
    $school_types = SchoolTypeHelper::getSchoolTypeVersions('GY');
    return $this->gradableCourseService->getGradableSyllabusIds($school_types);
  }

  protected function getTableHeader(): array {
    return [
      'subject' => $this->t('Subject'),
      'course_code' => $this->t('Course code'),
      'points' => $this->t('Points'),
      'date' => $this->t('Date'),
      'grade' => $this->t('Grade'),
    ];
  }

  protected function getSnapshotLimit(): int {
    return 1;
  }

  protected function getSchoolTypeVersions(): array {
    return SchoolTypeHelper::getSchoolTypeVersions('GY');
  }

}
