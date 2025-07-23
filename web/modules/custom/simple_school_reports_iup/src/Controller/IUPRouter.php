<?php

namespace Drupal\simple_school_reports_iup\Controller;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\simple_school_reports_core\Form\RangeToUrlForm;
use Drupal\simple_school_reports_core\Plugin\Block\InvalidAbsenceStudentStatisticsBlock;
use Drupal\simple_school_reports_core\Service\TermServiceInterface;
use Drupal\simple_school_reports_grade_stats\Plugin\Block\StudentGradeStatisticsBlock;
use Drupal\simple_school_reports_reviews\Service\WrittenReviewsRoundProgressServiceInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Route;
use function Symfony\Component\String\s;

/**
 * Controller for UserPageController.
 */
class IUPRouter extends ControllerBase {

  /**
   * The current request.
   */
  protected Request $currentRequest;

  /**
   * The current route match.
   */
  protected RouteMatchInterface $currentRouteMatch;

  /**
   * The block manager.
   */
  protected BlockManagerInterface $blockManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    RequestStack $request_stack,
    RouteMatchInterface $route_match,
    BlockManagerInterface $block_manager
  ) {
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->currentRouteMatch = $route_match;
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('current_route_match'),
      $container->get('plugin.manager.block'),
    );
  }

  public function studentTab() {
    return [];
  }

  public function getStudentTabTitle() {
    $user = $this->currentRouteMatch->getParameter('user');
    if ($user && $user instanceof UserInterface) {
      return $user->getDisplayName() . ' - ' . $this->t('IUP');
    }

    return $this->t('IUP');
  }

  public function router(string $round_nid, string $student_uid) {
    $query = $this->currentRequest->query->all();
    if (!empty($query['post_save_destination'])) {
      $query['destination'] = $query['post_save_destination'];
      unset($query['post_save_destination']);
    }

    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager()
      ->getStorage('node');
    /** @var NodeInterface $round_node */
    $round_node = $node_storage->load($round_nid);
    if (!$round_node || $round_node->bundle() !== 'iup_round') {
      throw new AccessDeniedHttpException();
    }

    $iup_nid = current($node_storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'iup')
      ->condition('field_student', $student_uid)
      ->condition('field_iup_round', $round_nid)
      ->execute()
    );
    $iup_node = $iup_nid ? $node_storage->load($iup_nid) : NULL;
    $iup_state = $iup_node ? $iup_node->get('field_state')->value : NULL;

    if (!$iup_state) {
      /** @var UserInterface $student */
      $student = $this->entityTypeManager()->getStorage('user')->load($student_uid);
      if ($student->hasRole('student')) {

        if (!$iup_node) {
          $name = '';
          _simple_school_reports_core_resolve_name($name, $student, TRUE);
          /** @var NodeInterface $iup_node */
          $iup_node = $this->entityTypeManager()->getStorage('node')->create([
            'type' => 'iup',
            'title' => 'IUP för ' . $name,
            'langcode' => 'sv',
          ]);
        }

        $iup_node->set('field_student', $student);
        $student_grade = $student->get('field_grade')->value;
        if ($student_grade) {
          $iup_node->set('field_grade', $student_grade);
        }

        $student_class_id = $student->get('field_class')->target_id;
        if ($student_class_id) {
          $iup_node->set('field_class', ['target_id' => $student_class_id]);
        };

        $iup_node->set('field_iup_round', $round_node);

        $this->resolveHDIGFieldDefault($iup_node, $round_node, $node_storage);
        $this->resolveWAWFieldDefault($iup_node, $round_node, $node_storage);
        $this->resolveHDWDISchoolFieldDefault($iup_node, $round_node, $node_storage);
        $iup_node->save();
        $query['open_first'] = 1;
      }
    }

    if ($iup_node->id()) {
      $route = 'entity.node.edit_form';
      if ($iup_node->get('field_state')->value === 'done' || $round_node->get('field_locked')->value) {
        $route = 'entity.node.canonical';
      }

      return new RedirectResponse(Url::fromRoute($route, ['node' => $iup_node->id()], ['query' => $query])->toString());
    }

    throw new AccessDeniedHttpException();
  }

  protected function hasPrefillOption(string $option, NodeInterface $round_node) {
    $prefill_values = array_column($round_node->get('field_prefill')->getValue(), 'value');
    return in_array($option, $prefill_values);
  }

  protected function cleanUpText(string $text) {
    $text = str_replace(PHP_EOL, '', $text);
    $text = str_replace('&nbsp;', ' ', $text);
    $text = str_replace('<p>', '', $text);
    $text = str_replace('</p>', '<br>', $text);

    $text = trim($text);

    // Remove last '<br>' if any.
    while(substr($text, -4) === '<br>') {
      $text = substr($text, 0, -4);
      $text = trim($text);
    }

    // Remove leading '<br>' if any.
    while(substr($text, 0, 4) === '<br>') {
      $text = substr($text, 4);
      $text = trim($text);
    }

    return $text;
  }

  /**
   * @param \Drupal\node\NodeInterface $iup_node
   * @param \Drupal\node\NodeInterface $round_node
   * @param \Drupal\node\NodeStorageInterface $node_storage
   */
  protected function resolveHDIGFieldDefault(NodeInterface &$iup_node, NodeInterface $round_node, NodeStorageInterface $node_storage) {
    $default_text = '';

    if ($this->hasPrefillOption('iup_goal', $round_node)) {
      $previous_round_nids = array_values($node_storage->getQuery()
        ->condition('type', 'iup_round')
        ->condition('nid', $round_node->id(), '<>')
        ->sort('nid', 'DESC')
        ->accessCheck(FALSE)
        ->execute());
      if (!empty($previous_round_nids)) {
        $previous_iup_nids = $node_storage->getQuery()
          ->condition('type', 'iup')
          ->condition('field_iup_round', $previous_round_nids, 'IN')
          ->condition('field_student', $iup_node->get('field_student')->target_id)
          ->condition('field_document_date', 0, '>')
          ->condition('field_document_date', (new \DateTime())->getTimestamp(), '<')
          ->sort('field_document_date', 'DESC')
          ->range(0,1)
          ->accessCheck(FALSE)
          ->execute();

        if (!empty($previous_iup_nids)) {
          /** @var NodeInterface $previous_iup_node */
          $latest_iup = $node_storage->load(current($previous_iup_nids));

          if ($latest_iup) {
            $default_text_parts = [];
            $iup_goals = $latest_iup->get('field_iup_goal_list')->referencedEntities();
            if (!empty($iup_goals)) {
              /** @var NodeInterface $goal */
              foreach ($iup_goals as $goal) {
                if ($goal->get('field_iup_goal')->value) {
                  $default_text_part = '';
                  /** @var TermInterface $subject */
                  if ($subject = current($goal->get('field_school_subject')->referencedEntities())) {
                    $default_text_part .= $subject->label() . ': ';
                  }
                  $default_text_part .= $this->cleanUpText($goal->get('field_iup_goal')->value);
                  if ($goal->get('field_teacher_comment')->value) {
                    $default_text_part .= '<br>*** Lärarkommentar: ***<br>' . $this->cleanUpText($goal->get('field_teacher_comment')->value);
                  }
                  $default_text_parts[] = $default_text_part;
                }
              }
            }
            $default_text = implode('<br><br>', $default_text_parts);
          }
        }
      }
    }

    $value = [
      'value' => $this->cleanUpText($default_text),
      'format' => 'plain_text_ck',
    ];
    $iup_node->set('field_hdig', $value);
  }

  protected function resolveWAWFieldDefault(NodeInterface &$iup_node, NodeInterface $round_node, NodeStorageInterface $node_storage) {
    $default_text = '';
    $student = $iup_node->get('field_student')->entity;

    if (!$student) {
      return;
    }

    $student_grade = $student->get('field_grade')->value;

    if ($this->hasPrefillOption('invalid_absence', $round_node)) {
      $invalid_absence = $student->get('field_invalid_absence')->value ?? 0;
      $default_text = 'Ogiltig frånvaro: ' . number_format($invalid_absence, 0, ',', ' ') . ' min';
      $default_text .= '<br>';
    }

    if ($this->hasPrefillOption('merit_' . ($student_grade ?? 'none'), $round_node)) {
      if (\Drupal::hasService('simple_school_reports_grade_stats.grade_statistics')) {
        $grade_stat_block = $this->blockManager->createInstance('student_grade_statistics_block');
        if ($grade_stat_block instanceof StudentGradeStatisticsBlock) {
          try {
            $block_build = $grade_stat_block
              ->setFallbackStudent($student)
              ->setSkipAccessCheck(TRUE)->build();
            // Resolve merit value.
            $first_round_key = !empty($block_build['table']['#ssr_sorted_row_keys'])
              ? reset($block_build['table']['#ssr_sorted_row_keys'])
              : 'no_rounds';
            $merit_value = !empty($block_build['table'][$first_round_key]['merit']['value']['#markup'])
              ? $block_build['table'][$first_round_key]['merit']['value']['#markup']
              : NULL;

            if ($merit_value) {
              $merit_value = number_format($merit_value, 1, ',', ' ');
              $merit_value = str_replace(',0', '', $merit_value);
              $default_text .= 'Betygsmerit: ' . $merit_value . ' p<br>';
            }
          }
          catch (\Exception $e) {
            // Do nothing.
          }
        }
      }
    }

    $value = [
      'value' => $this->cleanUpText($default_text),
      'format' => 'plain_text_ck',
    ];
    $iup_node->set('field_waw', $value);
  }

  protected function resolveHDWDISchoolFieldDefault(NodeInterface &$iup_node, NodeInterface $round_node, NodeStorageInterface $node_storage) {
    $default_text = '';
    $student = $iup_node->get('field_student')->entity;
    if (!$student) {
      return;
    }

    if ($this->hasPrefillOption('extra_adaptations', $round_node)) {
      $extra_adaptations = $student->get('field_extra_adaptations')->referencedEntities();
      if (!empty($extra_adaptations)) {
        $default_text .= 'Hjälpa till med extra anpassningar:<br>';
        foreach ($extra_adaptations as $extra_adaptation) {
          $extra_adaptation_text = $extra_adaptation->get('field_extra_adaptation')->entity?->get('field_extra_adaptation')->value;
          if (!$extra_adaptation_text) {
            continue;
          }

          $default_text .= $extra_adaptation_text;
          $subject_suffix_parts = [];

          foreach ($extra_adaptation->get('field_school_subjects')->referencedEntities() as $subject) {
            if ($subject->get('field_subject_code_new')->value) {
              $subject_suffix_parts[] = $subject->get('field_subject_code_new')->value;
            }
            elseif ($subject->label() === 'NO/SO') {
              $subject_suffix_parts[] = 'NO/SO';
            }
            elseif ($subject->label() === 'NO') {
              $subject_suffix_parts[] = 'NO';
            }
            elseif ($subject->label() === 'SO') {
              $subject_suffix_parts[] = 'SO';
            }
          }

          if (!empty($subject_suffix_parts)) {
            $default_text .= ' (' . implode(', ', $subject_suffix_parts) . ')';
          }
          $default_text .= '<br>';
        }
      }
    }

    $value = [
      'value' => $this->cleanUpText($default_text),
      'format' => 'plain_text_ck',
    ];
    $iup_node->set('field_hdwdi_school', $value);
  }
}
