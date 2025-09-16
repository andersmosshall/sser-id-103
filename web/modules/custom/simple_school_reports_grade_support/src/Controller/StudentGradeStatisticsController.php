<?php

namespace Drupal\simple_school_reports_grade_support\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\simple_school_reports_core\Controller\SsrCachedBlockPageControllerBase;
use Drupal\simple_school_reports_core\Utilities\SsrCachedBlockSettings;
use Drupal\user\UserInterface;

/**
 * Controller for GradeStatisticsController.
 */
class StudentGradeStatisticsController extends SsrCachedBlockPageControllerBase {

  /**
   * {@inheritdoc}
   */
  public function getBlockSettings(): array {
    $block_settings = [];
    if ($this->moduleHandler()->moduleExists('simple_school_reports_grade_stats')) {
      $block_settings[] = new SsrCachedBlockSettings(
        'student_grade_statistics_block',
        'Grundskolan',
      );
    }

    if ($this->moduleHandler()->moduleExists('simple_school_reports_grading_gy')) {
      $block_settings[] = new SsrCachedBlockSettings(
        'student_grade_statistics_block_gy',
        'Gymnasieskolan',
      );
    }
    return $block_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function pageId(): string {
    return 'user_grade_statistics';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(): string|TranslatableMarkup {
    $user = $this->routeMatch->getParameter('user');
    if ($user && $user instanceof UserInterface) {
      return $user->getDisplayName() . ' - ' . $this->t('Grade statistics');
    }

    return $this->t('Grade statistics');
  }

  public function buildPageContent(?UserInterface $user = NULL): array {
    $build = parent::buildPageContent($user);

    if (empty(Element::getVisibleChildren($build))) {
      $build['empty_info'] = [
        '#type' => 'html_tag',
        '#tag' => 'em',
        '#value' => $this->t('There are no grades to be shown yet.'),
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function access(?AccountInterface $account = NULL, ?UserInterface $user = NULL): AccessResultInterface {
    if (!$user) {
      return AccessResult::forbidden();
    }
    if (!$user->hasRole('student')) {
      return AccessResult::forbidden()->addCacheableDependency($user);
    }

    return parent::access($account, $user);
  }

}
