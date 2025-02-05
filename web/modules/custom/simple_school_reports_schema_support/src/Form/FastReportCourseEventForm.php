<?php

namespace Drupal\simple_school_reports_schema_support\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_entities\CalendarEventInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a confirmation form for fast report calendar event.
 */
class FastReportCourseEventForm extends MultipleFastReportCourseEventForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fast_report_course_event_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Fast report lesson');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL, ?CalendarEventInterface $ssr_calendar_event = NULL) {
    if (!$node || $node->bundle() !== 'course' || !$ssr_calendar_event || $ssr_calendar_event->bundle() !== 'course') {
      throw new AccessDeniedHttpException();
    }

    $course = $ssr_calendar_event->get('field_course')->target_id;
    if ($course !== $node->id()) {
      throw new NotFoundHttpException();
    }

    return parent::buildForm($form, $form_state, $node, $ssr_calendar_event);
  }

  public static function access(AccountInterface $account, ?NodeInterface $node = NULL, ?CalendarEventInterface $ssr_calendar_event = NULL) {
    if (!ssr_use_schema()) {
      return AccessResult::forbidden();
    }

    if (!$node || $node->bundle() !== 'course' || !$ssr_calendar_event || $ssr_calendar_event->bundle() !== 'course') {
      return AccessResult::forbidden();
    }

    if ($node->id() !== $ssr_calendar_event->get('field_course')->target_id) {
      return AccessResult::forbidden()->addCacheableDependency($node)->addCacheableDependency($ssr_calendar_event);
    }

    $access = AccessResult::allowedIf($node->access('update', $account, FALSE) && !$ssr_calendar_event->get('completed')->value);
    $access->addCacheContexts(['user']);
    $access->addCacheableDependency($node);
    $access->addCacheableDependency($ssr_calendar_event);

    return $access;
  }
}
