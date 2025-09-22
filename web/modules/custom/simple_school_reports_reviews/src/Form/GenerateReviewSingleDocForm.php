<?php

namespace Drupal\simple_school_reports_reviews\Form;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\file\FileInterface;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_reviews\WrittenReviewRoundFormAlter;
use Drupal\user\UserInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for generating review documents.
 */
class GenerateReviewSingleDocForm extends GenerateReviewDocsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'generate_reviews_catalog_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Generate written review document');
  }

  public function getCancelRoute() {
    return '<front>';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL, ?UserInterface $user = NULL) {
    if (!$node || $node->bundle() !== 'written_reviews_round') {
      throw new AccessDeniedHttpException();
    }

    if (!$user || !$user->hasRole('student')) {
      throw new AccessDeniedHttpException();
    }

    $form['student_uid'] = [
      '#type' => 'value',
      '#value' => $user->id(),
    ];

    $form = parent::buildForm($form, $form_state, $node);

    $form['#title'] = t('Generate written reviews document') . ' - ' . $user->getDisplayName();

    return $form;
  }

  protected function getCalculatedData(FormStateInterface $form_state) {
    if (!is_array($this->calculatedData)) {
      $batch = [
        'title' => $this->t('Generating written reviews documents'),
        'init_message' => $this->t('Generating written reviews documents'),
        'progress_message' => $this->t('Processed @current out of @total.'),
        'operations' => [],
        'finished' => [self::class, 'finished'],
      ];

      /** @var \Drupal\node\NodeStorage $node_storage */
      $node_storage = $this->entityTypeManager->getStorage('node');

      /** @var NodeInterface $grade_round */
      $written_reviews_round = $node_storage->load($form_state->getValue('written_reviews_round_nid'));

      $student_uid = $form_state->getValue('student_uid');

      if (!$written_reviews_round || !$student_uid) {
        $this->messenger()->addError(t('Something went wrong'));
        return;
      }

      $subject_names = [];
      $subject_ids = [];
      $grades = [];


      $written_reviews_subject_map = WrittenReviewRoundFormAlter::getWrittenReviewsSubjectMap($form_state, $written_reviews_round);

      $reviews_subject_nids = [];
      foreach ($written_reviews_subject_map as $grade => $class_data) {
        $grades[] = $grade;
        foreach ($class_data as $class_id => $subject_data) {
          foreach ($subject_data as $subject_id => $reviews_subject_nid) {
            $subject_ids[$subject_id] = TRUE;
            if ($reviews_subject_nid) {
              $reviews_subject_nids[] = $reviews_subject_nid;
            }
          }
        }
      }

      if (!empty($grades)) {
        $subjects = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple(array_keys($subject_ids));
        foreach ($subjects as $subject) {
          $subject_names[$subject->id()] = $subject->label();
        }
        asort($subject_names);
        if (!empty($subject_names) && !empty($reviews_subject_nids)) {
          $pids = [];

          $query = $this->connection->select('node__field_written_reviews', 'rs');
          $query->fields('rs', ['field_written_reviews_target_id']);
          $query->condition('rs.entity_id', $reviews_subject_nids, 'IN');
          $results = $query->execute();

          foreach ($results as $result) {
            $pids[] = $result->field_written_reviews_target_id;
          }
        }

        $student_uids = [];

        if (!empty($pids)) {
          $query = $this->connection->select('paragraph__field_student', 's');
          $query->fields('s', ['field_student_target_id', 'entity_id']);
          $query->condition('s.entity_id', $pids, 'IN');
          $query->condition('s.field_student_target_id', $student_uid);
          $results = $query->execute();

          foreach ($results as $result) {
            if ($result->field_student_target_id === $student_uid) {
              $student_uids[$result->field_student_target_id] = $result->field_student_target_id;
              $batch['operations'][] = [[self::class, 'resolveReview'], [$result->entity_id]];
            }
          }
        }

        $written_reviews_nids = $node_storage
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('field_written_reviews_round', $written_reviews_round->id())
          ->condition('field_student', $student_uid)
          ->condition('type', 'written_reviews')
          ->execute();

        foreach ($written_reviews_nids  as  $written_reviews_nid) {
          $batch['operations'][] = [[self::class, 'resolveSchoolEfforts'], [$written_reviews_nid]];
        }
      }

      $calculated_data = [
        'student_uids' => $student_uids,
        'subject_names' => $subject_names,
        'batch' => $batch,
      ];
      $this->calculatedData = $calculated_data;
    }

    return $this->calculatedData;
  }

  public static function finished($success, $results) {
    if (!$success || empty($results['latest_file_name']) || empty($results['latest_file_destination'])) {
      \Drupal::messenger()->addError(t('Something went wrong'));
      return;
    }

    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');

    $file_name = $results['latest_file_name'];
    $source_dir = 'public://' . $results['latest_file_destination'] . $file_name;
    $source_dir = $file_system->realpath($source_dir);

    /** @var UuidInterface $uuid_service */
    $uuid_service = \Drupal::service('uuid');
    $destination_dir = 'public://ssr_generated' . DIRECTORY_SEPARATOR . $uuid_service->generate() . DIRECTORY_SEPARATOR;
    $destination = $destination_dir . $file_name;
    $file_system->prepareDirectory($destination_dir, FileSystemInterface::CREATE_DIRECTORY);

    $file_system->copy($source_dir, $destination);

    /** @var FileInterface $file */
    $file = \Drupal::entityTypeManager()->getStorage('file')->create([
      'filename' => $file_name,
      'uri' => $destination,
    ]);
    $file->save();
    $path = $file->createFileUrl();
    $link = Markup::create('<a href="' . $path . '" target="_blank">' . t('here') . '</a>');
    $file_system->delete($source_dir);
    \Drupal::messenger()->addMessage(t('Written reviews file generation complete. Save the file in a secure location. This file will shortly be removed from server. Download it now form @link', ['@link' => $link]));
  }
}
