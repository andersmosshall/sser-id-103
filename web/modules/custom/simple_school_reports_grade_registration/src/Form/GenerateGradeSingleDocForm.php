<?php

namespace Drupal\simple_school_reports_grade_registration\Form;

use Drupal\Component\Uuid\Uuid;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\Form\GenerateSsnKeyForm;
use Drupal\simple_school_reports_core\Pnum;
use Drupal\simple_school_reports_core\Service\FileTemplateServiceInterface;
use Drupal\simple_school_reports_grade_registration\GradeRoundFormAlter;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for generating single grade doc.
 */
class GenerateGradeSingleDocForm extends GenerateGradeCatalogForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'generate_grade_single_doc_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Generate grade document');
  }

  public function getCancelRoute() {
    return '<front>';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL, ?UserInterface $user = NULL) {
    if (!$node || $node->bundle() !== 'grade_round') {
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

    unset($form['ssn_key']);
    unset($form['ssn_key_link']);

    $form['actions']['submit']['#skip_pnum_validation'] = TRUE;

    $form['#title'] = t('Generate grade document') . ' - ' . $user->getDisplayName();

    $form['actions']['submit']['#gen_submit'] = TRUE;

    return $form;
  }

  protected function getCalculatedData(FormStateInterface $form_state) {
    if (!is_array($this->calculatedData)) {
      $batch = [
        'title' => $this->t('Generating grade documents'),
        'init_message' => $this->t('Generating grade documents'),
        'progress_message' => $this->t('Processed @current out of @total.'),
        'operations' => [],
        'finished' => [self::class, 'finished'],
      ];

      $target_student_uid = $form_state->getValue('student_uid');

      if (!$target_student_uid) {
        $this->messenger()->addError(t('Something went wrong'));
        return;
      }


      $students = [];
      $teachers = [];
      $student_groups_data = [];

      $subjects = [];
      $catalog_ids = Settings::get('ssr_catalog_id');
      $excluded_catalog_label = Settings::get('ssr_excluded_catalog_label');
      $code_options = _simple_school_reports_core_school_subject_codes();

      foreach ($code_options as $code => &$label) {
        $label = preg_replace('/\(.+\)\s/', '', $label);
      }

      $subject_ids = $this->entityTypeManager
        ->getStorage('taxonomy_term')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('vid', 'school_subject')
        ->sort('name')
        ->execute();
      $weight = 1;

      if (!empty($subject_ids)) {
        foreach ($this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($subject_ids) as $subject) {
          $code = $subject->get('field_subject_code')->value;
          if (!$code || !isset($catalog_ids[$code]) || !isset($code_options[$code])) {
            continue;
          }
          $block_parent = $subject->get('field_block_parent')->target_id;
          if ($block_parent) {
            $subjects[$block_parent]['children'][] = $subject->id();
          }
          $subjects[$subject->id()]['weight'] = $weight;
          $subjects[$subject->id()]['name'] = $code_options[$code];
          $subjects[$subject->id()]['full_name'] = $subject->label();
          $subjects[$subject->id()]['catalog_id'] = $catalog_ids[$code];
          $subjects[$subject->id()]['catalog_com_id'] = isset($catalog_ids[$code. '_COM']) ? $catalog_ids[$code. '_COM'] : NULL;
          $subjects[$subject->id()]['code'] = $code;
          $subjects[$subject->id()]['parent'] = $block_parent;
          $subjects[$subject->id()]['excluded_label'] = isset($excluded_catalog_label[$code]) ? $excluded_catalog_label[$code] : '-';
          $weight++;
        }
      }

      /** @var \Drupal\node\NodeStorage $node_storage */
      $node_storage = $this->entityTypeManager->getStorage('node');


      /** @var NodeInterface $grade_round */
      $grade_round = $node_storage->load($form_state->getValue('grade_round_nid'));

      if (!$grade_round) {
        $this->messenger()->addError(t('Something went wrong'));
        return;
      }

      $student_groups = $grade_round->get('field_student_groups')->referencedEntities();

      /** @var NodeInterface $student_group */
      foreach ($student_groups as $student_group) {

        $student_uids = array_column($student_group->get('field_student')->getValue(), 'target_id');
        if (!in_array($target_student_uid, $student_uids)) {
          continue;
        }

        $student_uids = [$target_student_uid];
        $students[$target_student_uid]['groups'][$student_group->id()] = $student_group->id();

        $student_groups_data[$student_group->id()] = [
          'name' => $student_group->label(),
          'students' => $student_uids,
          'principle' => $student_group->get('field_principle')->target_id,
          'document_type' => $student_group->get('field_document_type')->value ?? '',
          'grade_registrations' => [],
          'grade_system' => $student_group->get('field_grade_system')->value,
        ];

        $grade_subject_nids = array_column($student_group->get('field_grade_subject')->getValue(), 'target_id');

        $query = $this->connection->select('node__field_grade_registration', 'g');
        $query->innerJoin('paragraph__field_student', 's', 's.entity_id = g.field_grade_registration_target_id');
        $query->fields('g', ['field_grade_registration_target_id']);
        $query->condition('s.field_student_target_id', $target_student_uid);
        $query->condition('g.entity_id', $grade_subject_nids, 'IN');
        $results = $query->execute();

        foreach ($results as $result) {
          $student_groups_data[$student_group->id()]['grade_registrations'][] = $result->field_grade_registration_target_id;
          $batch['operations'][] = [[self::class, 'resolveGrade'], [$result->field_grade_registration_target_id, $student_group->id(), $subjects]];
        }

        foreach ($grade_subject_nids as $grade_subject_nid) {
          $batch['operations'][] = [[self::class, 'resolveDefaultGrades'], [$grade_subject_nid, $student_group->id(), $student_groups_data[$student_group->id()], $subjects]];
        }

        if (!empty($student_groups_data[$student_group->id()]['grade_registrations'])) {
          $query = $this->connection->select('paragraph__field_teacher', 't');
          $query->fields('t', ['field_teacher_target_id']);
          $query->condition('t.entity_id', $student_groups_data[$student_group->id()]['grade_registrations'], 'IN');
          $results = $query->execute();

          foreach ($results as $result) {
            $teachers[$result->field_teacher_target_id]['groups'][$student_group->id()] = $student_group->id();
          }
        }

      }

      $calculated_data = [
        'students' => $students,
        'students_uids' => array_keys($students),
        'ordered_student_uids' => array_keys($students),
        'teachers' => $teachers,
        'student_groups_data' => $student_groups_data,
        'subjects' => $subjects,
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
    \Drupal::messenger()->addMessage(t('Grade file generation complete. Save the file in a secure location. This file will shortly be removed from server. Download it now form @link', ['@link' => $link]));
  }
}
