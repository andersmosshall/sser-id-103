<?php

namespace Drupal\simple_school_reports_child_care_support\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareSchemaServiceInterface;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareServiceInterface;
use Drupal\simple_school_reports_core\Controller\SsrCachedPageControllerBase;
use Drupal\simple_school_reports_core\Form\WeekNumberToUrlRangeForm;
use Drupal\user\UserInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for ChildCareStudentController.
 */
class ChildCareStudentController extends SsrCachedPageControllerBase {

  use RedirectDestinationTrait;

  protected ChildCareServiceInterface $childCareService;

  protected ChildCareSchemaServiceInterface $childCareSchemaService;

  protected Request $currentRequest;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->childCareService = $container->get('simple_school_reports_child_care_support.child_care');
    $instance->childCareSchemaService = $container->get('simple_school_reports_child_care_support.child_care_schema');
    $instance->currentRequest = $container->get('request_stack')->getCurrentRequest();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  public function buildPageContent(?UserInterface $user = NULL): array {
    $build = [];
    if (!$user) {
      throw new NotFoundHttpException();
    }

    $build['#attached']['library'][] = 'simple_school_reports_child_care_support/child_care_overview';

    $build['active_placements'] = [
      '#type' => 'container',
    ];
    $build['active_placements']['label'] = [
      '#markup' => '<div class="field field--label-above field-active-placements"><div class="field__label">' . $this->t('Active placements') . '</div>',
    ];

    $placement_ids = $this->childCareService->getChildCareGroups($user->id());
    /** @var \Drupal\simple_school_reports_child_care_support\ChildCareInterface[] $child_care_entities */
    $child_care_entities = !empty($placement_ids) ? $this->entityTypeManager->getStorage('ssr_child_care')->loadMultiple($placement_ids) : [];
    $child_care_names = [];
    foreach ($child_care_entities as $child_care_entity) {
      $child_care_names[] = $child_care_entity->label() . ' (' . $child_care_entity->getShortLabel() . ')';
    }
    sort($child_care_names);
    foreach ($child_care_names as $key => $child_care_name) {
      $build['active_placements'][$key] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $child_care_name,
      ];
    }

    $build['divider_placements'] = ['#markup' => '<hr>'];

    $build['week_form_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Week'),
    ];
    $build['week_form_wrapper']['week_form'] = $this->formBuilder()->getForm(WeekNumberToUrlRangeForm::class, TRUE);

    $from = $this->currentRequest->query->get('from');
    $to = $this->currentRequest->query->get('to');

    if ($from && $to) {
      $from_date = new \DateTime();
      $from_date->setTimestamp($from);

      $to_date = new \DateTime();
      $to_date->setTimestamp($to);

      $build['overview'] = $this->buildOverview($from_date, $to_date, $user);
    }

    $build['divider'] = ['#markup' => '<hr>'];

    $available_schema_view = Views::getView('child_care_available_schema');
    $available_schema_view->setDisplay('student_list');
    $available_schema_view->preExecute();
    $available_schema_view->execute();
    $available_schema_view_build = $available_schema_view->buildRenderable('student_list', [$user->id()]);
    $build['schema']['label'] = [
      '#markup' => '<div class="field field--label-above field-schema"><div class="field__label">' . t('Schema') . '</div>',
    ];
    $build['schema']['view'] = $available_schema_view_build;

    return $build;
  }

  protected function buildOverview(\DateTime $from_date, \DateTime $to_date, UserInterface $user): array {
    $build = [];
    if ($from_date > $to_date) {
      return $build;
    }

    $day_map = [
      1 => $this->t('Monday'),
      2 => $this->t('Tuesday'),
      3 => $this->t('Wednesday'),
      4 => $this->t('Thursday'),
      5 => $this->t('Friday'),
      6 => $this->t('Saturday'),
      7 => $this->t('Sunday'),
    ];

    // Do a date walk day by day.
    $current_date = clone $from_date;
    $current_date->setTime(0, 0, 0);

    while ($current_date <= $to_date) {
      $day_index = (int) $current_date->format('N');
      $day_label = $day_map[$day_index];

      $build[$day_index] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['child-care-day'],
        ],
      ];

      $build[$day_index]['label'] = [
        '#type' => 'html_tag',
        '#tag' => 'strong',
        '#value' => ucfirst($day_label . ' ' . $current_date->format('j/n')) . '<br>',
      ];

      $build[$day_index]['content'] = $this->buildOverviewDay($current_date, $user);

      if (($build[$day_index]['content']['#empty'] ?? FALSE) && ($day_index == 6 || $day_index == 7)) {
        unset($build[$day_index]);
      }

      $current_date->modify('+1 day');
    }



    return $build;
  }

  protected function buildOverviewDay(\DateTime $date, UserInterface $user): array {
    $build = [];

    $needs = $this->childCareSchemaService->getChildCareStudentNeed($user->id(), $date) ?? [];
    $deviation_id = $needs['deviation_id'] ?? NULL;
    $schema_id = $needs['schema_id'] ?? NULL;
    $child_care_ids = $needs['child_care_ids'] ?? [];

    if ($deviation_id || $schema_id) {
      $from = $needs['from'] ?? NULL;
      $to = $needs['to'] ?? NULL;
      if ($from && $to) {
        $base_date = clone $date;
        $base_date->setTime(0, 0, 0);
        $from = $from - $base_date->getTimestamp();
        $to = $to - $base_date->getTimestamp();
      }

      $today = new \DateTime();
      $today->setTime(0, 0, 0);

      $day = (int) $date->format('N');
      $future_max_limit = $this->childCareService->getSettings()['future_max_limit'];
      $max_limit = new \DateTime();
      $max_limit->setTime(0, 0, 0);
      $max_limit->add(new \DateInterval('P' . $future_max_limit . 'D'));

      if ($date >= $today && $date < $max_limit && ($day !== 6 && $day !== 7 || $deviation_id)) {
        $query = [
          'destination' => $this->getDestinationArray()['destination'],
          'deviation_id' => $deviation_id,
          'from' => $from,
          'to' => $to,
        ];
        $url = Url::fromRoute('simple_school_reports_child_care_support.add_student_schema_deviation', [
          'user' => $user->id(),
          'date' => $date->format('Y-m-d'),
        ], ['query' => $query]);
        $build['deviation'] = [
          '#type' => 'link',
          '#title' => $this->t('Change'),
          '#attributes' => ['class' => ['button', 'button--small', 'button--primary', 'button--change-child-care-day']],
          '#url' => $url,
        ];
      }
    }

    $build['segments'] = $this->childCareSchemaService->buildStudentDayOverview($user->id(), $date);

    if ($deviation_id) {
      $deviation = $this->entityTypeManager->getStorage('ssr_cc_deviation_student')->load($deviation_id);
      if ($deviation) {
        $build['changed_by'] = [
          '#type' => 'html_tag',
          '#tag' => 'em',
          '#value' => $this->t('Changed by @name', ['@name' => $deviation->get('uid')->entity->getDisplayName()]),
        ];
      }
    }

    $build['#empty'] = !$deviation_id && ($build['segments']['#empty'] ?? FALSE) === TRUE;

    return $build;
  }

  public function getCacheableMetadata(): CacheableMetadata {
    $cache = parent::getCacheableMetadata();

    $cache->addCacheTags([
      'school_week_list',
      'node_list:day_absence',
      'school_week_deviation_list',
      'ssr_school_week_per_grade',
      'ssr_child_care_list',
      'ssr_child_care_placement_list',
      'ssr_child_care_schema_list',
      'ssr_cc_deviation_list',
      'ssr_cc_deviation_student_list',
    ]);
    $cache->addCacheContexts(['url.query_args:from', 'url.query_args:to']);

    // TEMP!!
    $cache->setCacheMaxAge(0);

    return $cache;
  }

  public function pageId(): string {
    return 'child_care_student';
  }

  public function getTitle(): string|TranslatableMarkup {
    $user = $this->routeMatch->getParameter('user');
    if ($user && $user instanceof UserInterface) {
      return $user->getDisplayName() . ' - ' . $this->t('Child care');
    }
    return $this->t('Child care');
  }

  public function accessStudentChildCare(UserInterface $user = NULL, AccountInterface $account = NULL): AccessResult {
    if (!ssr_use_child_care() || !$user) {
      return AccessResult::forbidden()->addCacheableDependency(parent::access());
    }

    if (!$user->hasRole('student') || !$user->access('caregiver_access', $account)) {
      return AccessResult::forbidden()->addCacheableDependency(parent::access())->addCacheableDependency($user);
    }

    $group_ids = $this->childCareService->getChildCareGroups($user->id());
    return AccessResult::allowedIf(!empty($group_ids))->addCacheableDependency(parent::access())->addCacheableDependency($user);
  }

}
