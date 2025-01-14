<?php

namespace Drupal\simple_school_reports_reviews\Form;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\Pnum;
use Drupal\simple_school_reports_core\Service\FileTemplateServiceInterface;
use Drupal\simple_school_reports_reviews\WrittenReviewRoundFormAlter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for generating review documents.
 */
class GenerateReviewDocsForm extends ConfirmFormBase {

  /**
   * @var \Drupal\simple_school_reports_core\Service\FileTemplateServiceInterface
   */
  protected $fileTemplateService;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * @var UuidInterface
   */
  protected $uuid;

  protected $pnum;

  protected $calculatedData;

  const LETTER_INDEX = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'X', 'Y', ];



  public function __construct(FileTemplateServiceInterface $file_template_service, EntityTypeManagerInterface $entity_type_manager, Connection $connection,  UuidInterface $uuid, Pnum $pnum) {
    $this->fileTemplateService = $file_template_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->uuid = $uuid;
    $this->pnum = $pnum;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_school_reports_core.file_template_service'),
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('uuid'),
      $container->get('simple_school_reports_core.pnum'),
    );
  }

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
    return $this->t('Generate review catalog');
  }

  public function getCancelRoute() {
    return 'view.written_reviews_rounds.list';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url($this->getCancelRoute());
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Generate');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL) {
    if (!$node || $node->bundle() !== 'written_reviews_round') {
      throw new AccessDeniedHttpException();
    }

    $form['written_reviews_round_nid'] = [
      '#type' => 'value',
      '#value' => $node->id(),
    ];

    if ($node->get('field_document_date')->value && $node->get('field_locked')->value) {
      $doc_date = new \DateTime();
      $doc_date->setTimestamp($node->get('field_document_date')->value);
      $doc_date->add(new \DateInterval('P1M'));

      $limit = new \DateTime();
      $limit->setTime(0,0,0);

      if ($doc_date < $limit) {
        $messages = [
          'warning' => [
            $this->t('Note. Information in documents is regenerated. I.e. information that may have changed since the regular time of the round, such as student name or mentor etc., may therefore be out of date. For documents current at the time of the round please download from the school\'s local archives.'),
          ],
        ];

        $form['message'] = [
          '#theme' => 'status_messages',
          '#message_list' => $messages,
          '#status_headings' => [
            'status' => $this->t('Status message'),
            'error' => $this->t('Error message'),
            'warning' => $this->t('Warning message'),
          ],
        ];
      }
    }

    if ($this->getFormId() === 'generate_reviews_catalog_form' && !$node->get('field_locked')->value) {
      $form['lock'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Lock'),
        '#description' => $this->t('Lock this round for future registrations, can be unlocked by editing the round later.'),
        '#default_value' => FALSE,
      ];
    }

    $form = parent::buildForm($form, $form_state);

    $form['#title'] = t('Generate written reviews documents') . ' - ' . $node->label();

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate file exists.
    $required_templates = [
      'written_reviews',
      'doc_logo_left',
    ];
    foreach ($required_templates as $required_template) {
      if (!$this->fileTemplateService->getFileTemplateRealPath($required_template)) {
        $form_state->setError($form, $this->t('File template missing.'));
        return;
      }
    }
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

      /** @var NodeInterface $written_reviews_round */
      $written_reviews_round = $node_storage->load($form_state->getValue('written_reviews_round_nid'));

      if (!$written_reviews_round) {
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
            $batch['operations'][] = [[self::class, 'resolveReview'], [$result->field_written_reviews_target_id]];
          }
        }

        $student_uids = [];

        if (!empty($pids)) {
          $query = $this->connection->select('paragraph__field_student', 's');
          $query->fields('s', ['field_student_target_id']);
          $query->condition('s.entity_id', $pids, 'IN');
          $results = $query->execute();

          foreach ($results as $result) {
            $student_uids[$result->field_student_target_id] = $result->field_student_target_id;
          }
        }

        $written_reviews_nids = $this->entityTypeManager->getStorage('node')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('field_written_reviews_round', $written_reviews_round->id())
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

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect($this->getCancelRoute());

    $calculated_data = $this->getCalculatedData($form_state);

    $subject_names = $calculated_data['subject_names'];
    $student_uids = $calculated_data['student_uids'];
    $batch = $calculated_data['batch'];

    if (!empty($batch['operations'])) {
      /** @var \Drupal\node\NodeStorage $node_storage */
      $node_storage = $this->entityTypeManager->getStorage('node');

      /** @var NodeInterface $written_reviews_round */
      $written_reviews_round = $node_storage->load($form_state->getValue('written_reviews_round_nid'));

      if (!$written_reviews_round) {
        $this->messenger()->addError(t('Something went wrong'));
        return;
      }

      $document_date = '';
      $timestamp = $written_reviews_round->get('field_document_date')->value;

      if ($timestamp) {
        $date = new DrupalDateTime();
        $date->setTimestamp($timestamp);
        $document_date = $date->format('Y-m-d');
      }

      $references = [
        'subject_names' => $subject_names,
        'term_type' => $written_reviews_round->get('field_term_type')->value,
        'term_type_full' => WrittenReviewRoundFormAlter::getFullTermStamp($written_reviews_round),
        'document_date' => $document_date,
        'base_destination' => $this->uuid->generate(),
        'written_reviews_round_name' => $written_reviews_round->label(),
        'written_reviews_round_id' => $written_reviews_round->id(),
      ];

      foreach ($student_uids as $student_uid) {
        $batch['operations'][] = [[self::class, 'generateStudentReviewDoc'], [$student_uid, $references]];
      }

      if ($form_state->getValue('lock', FALSE)) {
        $written_reviews_round->set('field_locked', TRUE);
        $written_reviews_round->save();
      }

      batch_set($batch);
    }
    else {
      $this->messenger()->addWarning($this->t('No written reviews has been registered.'));
    }
  }

  public static function resolveReview($pargraph_id, &$context) {
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = \Drupal::entityTypeManager()->getStorage('paragraph')->load($pargraph_id);
    if (!$paragraph) {
      return;
    }

    $student_uid = $paragraph->get('field_student')->target_id;
    $review_subject = $paragraph->getParentEntity();
    if (!$student_uid || !$review_subject) {
      return;
    }

    $subject_id = $review_subject->get('field_school_subject')->target_id;

    if (!$subject_id) {
      return;
    }

    $review = $paragraph->get('field_review')->value ?? NULL;
    $review_comment = $paragraph->get('field_review_comment')->value ?? '';
    if ($review) {
      $context['results']['student_review'][$student_uid][$subject_id]['review'] = $review;
      $context['results']['student_review'][$student_uid][$subject_id]['comment'] = $review_comment;
    }
  }

  public static function resolveSchoolEfforts($written_review_nid, &$context) {
    /** @var NodeInterface $node */
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($written_review_nid);
    if (!$node) {
      return;
    }

    $student_uid = $node->get('field_student')->target_id;
    $text = $node->get('field_school_efforts')->value;

    if ($student_uid && $text) {
      $context['results']['student_school_efforts'][$student_uid]['text'] = $text;
    }

    $student_grade = $node->get('field_grade')->value ?? '?';
    $student_class = $node->get('field_class')->entity ?? NULL;
    if ($student_grade < 1 && $student_grade > 9) {
      $student_grade = '?';
    }
    $context['results']['student_grade'][$student_uid] = $student_grade;
    $use_classes = \Drupal::moduleHandler()->moduleExists('simple_school_reports_class');
    if ($use_classes && $student_class) {
      $context['results']['student_class'][$student_uid] = $student_class->label();
    }
  }


  public static function generateStudentReviewDoc($student_uid, $references, &$context) {
    if (empty($context['results']['student_review'][$student_uid])) {
      return;
    }

    /** @var FileTemplateServiceInterface $file_template_service */
    $file_template_service = \Drupal::service('simple_school_reports_core.file_template_service');

    $template_file = 'written_reviews';
    if (!$file_template_service->getFileTemplateRealPath($template_file)) {
      return;
    }

    /** @var \Drupal\user\UserInterface $student */
    $student = \Drupal::entityTypeManager()->getStorage('user')->load($student_uid);

    if (!$student || !$student->hasRole('student')) {
      return;
    }

    $subject_names = $references['subject_names'];
    $student_grade = !empty($context['results']['student_grade'][$student_uid]) ? $context['results']['student_grade'][$student_uid] : '?';
    $student_class_name = !empty($context['results']['student_class'][$student_uid]) ? $context['results']['student_class'][$student_uid] : NULL;
    $search_replace_map = [];
    $search_replace_map['!datum!'] = $references['document_date'];

    $first_name = $student->get('field_first_name')->value ?? '';
    $last_name = $student->get('field_last_name')->value ?? '';
    $search_replace_map['!namn!'] = trim($first_name . ' ' . $last_name);
    $search_replace_map['!ak!'] = $student_grade;
    if ($student_class_name) {
      $search_replace_map['!ak!'] .= ' (' . $student_class_name . ')';
    }
    $search_replace_map['!termin!'] = mb_strtoupper($references['term_type_full']);

    $context['results']['students_name'][$student_uid] = $first_name . ' ' . $last_name;
    $context['results']['students_first_name'][$student_uid] = $first_name;
    $context['results']['students_last_name'][$student_uid] = $last_name;

    $mentor_name = '';
    /** @var \Drupal\user\UserInterface $mentor */
    $mentor = current($student->get('field_mentor')->referencedEntities());
    if ($mentor) {
      $mentor_name = $mentor->getDisplayName();
    }
    $search_replace_map['!mentor!'] = $mentor_name;

    $file_name = str_replace(' ', '_', 'skriftligt_omdöme_' . $references['term_type_full'] . '_' . $context['results']['students_name'][$student_uid] . '_' . $student->id());
    $file_name = str_replace('/', '-', $file_name);
    $file_name = str_replace('\\', '-', $file_name);
    $file_name = mb_strtolower($file_name);

    $event = new FileUploadSanitizeNameEvent($file_name, '');
    $dispatcher = \Drupal::service('event_dispatcher');
    $dispatcher->dispatch($event);
    $file_name = $event->getFilename();

    $generate_file = FALSE;

    $reviews = $context['results']['student_review'][$student_uid];

    // Set some default values.
    $max_size = Settings::get('ssr_max_written_reviews_subject_list', 20);
    for ($row_id = 1; $row_id <= $max_size; $row_id++) {
      $search_replace_map['!a' . $row_id . '!'] = '';
      $search_replace_map['!o' . $row_id . '!'] = '';
      $search_replace_map['!g' . $row_id . '!'] = '';
      $search_replace_map['!m' . $row_id . '!'] = '';
      $search_replace_map['!k' . $row_id . '!'] = '';

    }


    $row_id = 1;
    foreach ($subject_names as $subject_id => $subject_name) {
      if (!empty($reviews[$subject_id]['review']) && in_array($reviews[$subject_id]['review'], ['na', 'ik', 'ak', 'mak'])) {
        $review = $reviews[$subject_id]['review'];
        $comment = $reviews[$subject_id]['comment'];

        $search_replace_map['!a' . $row_id . '!'] = $subject_name;
        $search_replace_map['!o' . $row_id . '!'] = FileTemplateServiceInterface::WORD_CHECKBOX_UNCHECKED;
        $search_replace_map['!g' . $row_id . '!'] = FileTemplateServiceInterface::WORD_CHECKBOX_UNCHECKED;
        $search_replace_map['!m' . $row_id . '!'] = FileTemplateServiceInterface::WORD_CHECKBOX_UNCHECKED;
        $search_replace_map['!k' . $row_id . '!'] = $file_template_service->handleFormattedWordText($comment);

        if ($review === 'ik') {
          $search_replace_map['!o' . $row_id . '!'] = FileTemplateServiceInterface::WORD_CHECKBOX_CHECKED;
        }
        elseif ($review === 'ak') {
          $search_replace_map['!g' . $row_id . '!'] = FileTemplateServiceInterface::WORD_CHECKBOX_CHECKED;
        }
        elseif ($review === 'mak') {
          $search_replace_map['!m' . $row_id . '!'] = FileTemplateServiceInterface::WORD_CHECKBOX_CHECKED;
        }

        $generate_file = TRUE;
        $row_id++;
      }
    }

    $search_replace_map['!rutanl!'] = 'Skolans insatser - en beskrivning av skolans ansvar för hur skolan ger stöd och stimulans för att eleven ska utvecklas så långt som möjligt. Beskrivningen är ett underlag för den framåtsyftande planeringen.';
    $search_replace_map['!rutan!'] = '';
    $school_efforts = !empty($context['results']['student_school_efforts'][$student_uid]['text']) ? $context['results']['student_school_efforts'][$student_uid]['text'] : '';
    if ($school_efforts) {
      $generate_file = TRUE;
      $search_replace_map['!rutan!'] = $file_template_service->handleFormattedWordText($school_efforts);
    }

    if ($generate_file) {
      if ($student_grade === '?') {
        $student_grade = 'annan';

        // Check if there is no student_grade info (e.g. no written_review_nid).
        if (empty($context['results']['student_grade'][$student_uid])) {
          /** @var \Drupal\simple_school_reports_reviews\Service\WrittenReviewsRoundProgressServiceInterface $progress_service */
          $progress_service = \Drupal::service('simple_school_reports_reviews.written_reviews_round_progress_service');
          $written_reviews_nid = $progress_service->getWrittenReviewsNid($references['written_reviews_round_id'], $student_uid);
          if (!$written_reviews_nid) {
            $round_node = \Drupal::entityTypeManager()->getStorage('node')->load($references['written_reviews_round_id']);

            if ($round_node && $round_node->bundle() === 'written_reviews_round') {
              $name = '';
              _simple_school_reports_core_resolve_name($name, $student, TRUE);

              $written_reviews_node = \Drupal::entityTypeManager()->getStorage('node')->create([
                'type' => 'written_reviews',
                'title' => 'Skriftligt omdöme för ' . $name,
                'langcode' => 'sv',
              ]);

              $written_reviews_node->set('field_student', $student);
              $student_grade = $student->get('field_grade')->value;
              if ($student_grade) {
                $written_reviews_node->set('field_grade', $student_grade);
                $written_reviews_node->set('field_written_reviews_round', $round_node);

                $student_class = $student->get('field_class')->entity;
                if ($student_class) {
                  $student_class_name = $student_class->label();
                  $written_reviews_node->set('field_class', $student_class);
                };

                $written_reviews_node->save();
                $written_reviews_nid = $written_reviews_node->id();
              }
            }
          }
          if ($written_reviews_nid) {
            self::resolveSchoolEfforts($written_reviews_nid, $context);
          }
          $student_grade = !empty($context['results']['student_grade'][$student_uid]) ? $context['results']['student_grade'][$student_uid] : '?';
          $search_replace_map['!ak!'] = $student_grade;
          if ($student_class_name) {
            $search_replace_map['!ak!'] .= ' (' . $student_class_name . ')';
          }
          if ($student_grade === '?') {
            $student_grade = 'annan';
          }
        }
      }
      $student_group_dest = 'arskurs_' . $student_grade;
      if ($student_class_name) {
        $student_group_dest .= '_' . mb_strtolower(str_replace(' ', '_', $student_class_name));
      }

      $file_dest = $references['base_destination'] . DIRECTORY_SEPARATOR . $student_group_dest . DIRECTORY_SEPARATOR;
      if ($file_template_service->generateDocxFile($template_file, $file_dest, $file_name, $search_replace_map, 'doc_logo_left')) {
        self::makeSureMetaDataInContext($references, $context);
        $context['results']['latest_file_destination'] = 'ssr_tmp' . DIRECTORY_SEPARATOR .  $file_dest;
        $context['results']['latest_file_name'] = $file_name . '.docx';
      }
    }
  }

  public static function makeSureMetaDataInContext($references, &$context) {
    $context['results']['base_destination'] = $references['base_destination'];
    $context['results']['written_reviews_round_name'] = $references['written_reviews_round_name'];
  }

  public static function finished($success, $results) {
    if (!$success || empty($results['base_destination'])) {
      \Drupal::messenger()->addError(t('Something went wrong'));
      return;
    }

    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $source_dir = 'public://ssr_tmp' . DIRECTORY_SEPARATOR . $results['base_destination'] . DIRECTORY_SEPARATOR;
    $source_dir = $file_system->realpath($source_dir);
    /** @var UuidInterface $uuid_service */
    $uuid_service = \Drupal::service('uuid');
    $destination = 'ssr_generated' . DIRECTORY_SEPARATOR . $uuid_service->generate() . DIRECTORY_SEPARATOR;

    $now = new DrupalDateTime();
    $file_name = str_replace(' ', '_', $results['written_reviews_round_name']) . '_' . \Drupal::service('uuid')->generate() . '.zip';
    $event = new FileUploadSanitizeNameEvent($file_name, 'zip');
    $dispatcher = \Drupal::service('event_dispatcher');
    $dispatcher->dispatch($event);
    $file_name = $event->getFilename();

    /** @var FileTemplateServiceInterface $file_template_service */
    $file_template_service = \Drupal::service('simple_school_reports_core.file_template_service');

    if ($file_template_service->doZip($source_dir, $destination, $file_name)) {
      /** @var FileInterface $file */
      $file = \Drupal::entityTypeManager()->getStorage('file')->create([
        'filename' => $file_name,
        'uri' => 'public://' . $destination . $file_name,
      ]);
      $file->save();
      $path = $file->createFileUrl();
      $link = Markup::create('<a href="' . $path . '" target="_blank">' . t('here') . '</a>');
      \Drupal::messenger()->addMessage(t('Written reviews file generation complete. Save the file in a secure location. This file will shortly be removed from server. Download it now form @link', ['@link' => $link]));
      $file_system->deleteRecursive($source_dir);
      return;
    };
    \Drupal::messenger()->addError(t('Something went wrong'));
  }
}
