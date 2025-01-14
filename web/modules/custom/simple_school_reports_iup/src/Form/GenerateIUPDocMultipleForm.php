<?php

namespace Drupal\simple_school_reports_iup\Form;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\Service\FileTemplateServiceInterface;
use Drupal\simple_school_reports_iup\IUPFormAlter;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for generating IUP documents.
 */
class GenerateIUPDocMultipleForm extends ConfirmFormBase {

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

  protected $calculatedData;

  const LETTER_INDEX = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'X', 'Y', ];



  public function __construct(FileTemplateServiceInterface $file_template_service, EntityTypeManagerInterface $entity_type_manager, Connection $connection,  UuidInterface $uuid) {
    $this->fileTemplateService = $file_template_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->uuid = $uuid;
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
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'generate_iup_docs_multiple_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Generate IUP documents');
  }

  public function getCancelRoute() {
    return 'view.iup_rounds.list';
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
    if (!$node || $node->bundle() !== 'iup_round') {
      throw new AccessDeniedHttpException();
    }

    $form['iup_round_nid'] = [
      '#type' => 'value',
      '#value' => $node->id(),
    ];

    if ($node->getCreatedTime() && $node->get('field_locked')->value) {
      $doc_date = new \DateTime();
      $doc_date->setTimestamp($node->getCreatedTime());
      $doc_date->add(new \DateInterval('P2M'));

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

    if ($this->getFormId() === 'generate_iup_docs_multiple_form' && !$node->get('field_locked')->value) {
      $form['lock'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Lock'),
        '#description' => $this->t('Lock this round for future registrations, can be unlocked by editing the round later.'),
        '#default_value' => FALSE,
      ];
    }

    $form = parent::buildForm($form, $form_state);

    $form['#title'] = t('Generate iup documents') . ' - ' . $node->label();

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate file exists.
    $required_templates = [
      'iup',
      'doc_logo_left',
    ];
    foreach ($required_templates as $required_template) {
      if (!$this->fileTemplateService->getFileTemplateRealPath($required_template)) {
        $form_state->setError($form, $this->t('File template missing.'));
        return;
      }
    }
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect($this->getCancelRoute());

    $batch = [
      'title' => $this->t('Generating iup documents'),
      'init_message' => $this->t('Generating iup documents'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'operations' => [],
    ];

    /** @var \Drupal\node\NodeStorage $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');

    /** @var NodeInterface $iup_round */
    $iup_round = $node_storage->load($form_state->getValue('iup_round_nid'));

    if (!$iup_round) {
      $this->messenger()->addError(t('Something went wrong'));
      return;
    }

    $iup_nids = $node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'iup')
      ->condition('status', '1')
      ->condition('field_iup_round', $iup_round->id())
      ->execute();

    $references = self::generateReferences($iup_round, $this->uuid->generate());

    foreach ($iup_nids as $iup_nid) {
      $batch['operations'][] = [[self::class, 'generateIUPDoc'], [$iup_nid, $references]];
    }

    if (!empty($batch['operations'])) {
      $batch['finished'] = [self::class, 'finished'];

      if ($form_state->getValue('lock', FALSE)) {
        $iup_round->set('field_locked', TRUE);
        $iup_round->save();
      }

      batch_set($batch);
    }
    else {
      $this->messenger()->addWarning($this->t('No IUP has been registered.'));
    }
  }

  public static function generateReferences(NodeInterface $iup_round, string $base_destination): array {
    return [
      'term_type' => $iup_round->get('field_term_type')->value,
      'term_type_full' => IUPFormAlter::getFullTermStamp($iup_round),
      'base_destination' => $base_destination,
      'iup_round_name' => $iup_round->label(),
    ];
  }

  public static function generateIUPDoc($iup_nid, $references, &$context) {
    /** @var FileTemplateServiceInterface $file_template_service */
    $file_template_service = \Drupal::service('simple_school_reports_core.file_template_service');

    $template_file = 'iup';
    if (!$file_template_service->getFileTemplateRealPath($template_file)) {
      return;
    }

    $iup = \Drupal::entityTypeManager()->getStorage('node')->load($iup_nid);
    if (!$iup || $iup->bundle() !== 'iup' || $iup->get('field_state')->isEmpty()) {
      return;
    }

    /** @var \Drupal\user\UserInterface $student */
    $student = current($iup->get('field_student')->referencedEntities());
    if (!$student || !$student->hasRole('student')) {
      return;
    }
    $student_uid = $student->id();

    $student_grade = $iup->get('field_grade')->value ?? '?';
    if ($student_grade < 1 && $student_grade > 9) {
      $student_grade = '?';
    }

    $use_classes = \Drupal::moduleHandler()->moduleExists('simple_school_reports_class');
    /** @var \Drupal\simple_school_reports_class_support\SchoolClassInterface|null $student_class */
    $student_class = $use_classes && !$iup->get('field_class')->isEmpty()
      ? $student->get('field_class')->entity
      : NULL;

    $search_replace_map = [];

    $document_date = '';
    $timestamp = $iup->get('field_document_date')->value;
    if ($timestamp) {
      $date = new DrupalDateTime();
      $date->setTimestamp($timestamp);
      $document_date = $date->format('Y-m-d');
    }
    $search_replace_map['!datum!'] = $document_date;

    $first_name = $student->get('field_first_name')->value ?? '';
    $last_name = $student->get('field_last_name')->value ?? '';
    $search_replace_map['!namn!'] = trim($first_name . ' ' . $last_name);
    $search_replace_map['!ak!'] = $student_grade;

    if ($student_class) {
      $search_replace_map['!ak!'] .=  ' (' . $student_class->label() . ')';
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

    $file_name = str_replace(' ', '_', 'iup_' . $references['term_type_full'] . '_' . $context['results']['students_name'][$student_uid] . '_' . $student->id());
    $file_name = str_replace('/', '-', $file_name);
    $file_name = str_replace('\\', '-', $file_name);
    $file_name = mb_strtolower($file_name);

    $event = new FileUploadSanitizeNameEvent($file_name, '');
    $dispatcher = \Drupal::service('event_dispatcher');
    $dispatcher->dispatch($event);
    $file_name = $event->getFilename();

    // Set some defaults.
    $search_replace_map['!hgd!'] = '';
    $search_replace_map['!vav!'] = '';
    $search_replace_map['!vsvm!'] = '';
    $search_replace_map['!hgvs!'] = '';
    $search_replace_map['!hgve!'] = '';
    $search_replace_map['!hgvv!'] = '';



    if ($iup->get('field_hdig')->value) {
      $search_replace_map['!hgd!'] = $file_template_service->handleFormattedWordText($iup->get('field_hdig')->value, 'plain_text_ck');
    }

    if ($iup->get('field_waw')->value) {
      $search_replace_map['!vav!'] = $file_template_service->handleFormattedWordText($iup->get('field_waw')->value, 'plain_text_ck');
    }

    if ($iup->get('field_hdwdi_school')->value) {
      $search_replace_map['!hgvs!'] = $file_template_service->handleFormattedWordText($iup->get('field_hdwdi_school')->value, 'plain_text_ck');
    }

    if ($iup->get('field_hdwdi_student')->value) {
      $search_replace_map['!hgve!'] = $file_template_service->handleFormattedWordText($iup->get('field_hdwdi_student')->value, 'plain_text_ck');
    }

    if ($iup->get('field_hdwdi_caregiver')->value) {
      $search_replace_map['!hgvv!'] = $file_template_service->handleFormattedWordText($iup->get('field_hdwdi_caregiver')->value, 'plain_text_ck');
    }

    $goal_parts = [];

    /** @var NodeInterface $iup_goal */
    foreach ($iup->get('field_iup_goal_list')->referencedEntities() as $iup_goal) {
      if ($iup_goal->get('field_iup_goal')->value) {
        $goal_part = '';
        /** @var TermInterface $subject */
        if ($subject = current($iup_goal->get('field_school_subject')->referencedEntities())) {
          $goal_part = $subject->label() . ':<br>';
        }
        $goal_part .= $iup_goal->get('field_iup_goal')->value;
        $goal_parts[] = $goal_part;
      }
    }

    if (!empty($goal_parts)) {
      $search_replace_map['!vsvm!'] = $file_template_service->handleFormattedWordText(implode('<br><br>', $goal_parts), 'plain_text_ck');
    }


    if ($student_grade === '?') {
      $student_grade = 'annan';
    }
    $student_group_dest = 'arskurs_' . $student_grade;
    if ($student_class) {
      $student_group_dest .= '_' . mb_strtolower(str_replace(' ', '_', $student_class->label()));
    }
    $file_dest = $references['base_destination'] . DIRECTORY_SEPARATOR . $student_group_dest . DIRECTORY_SEPARATOR;
    if ($file_template_service->generateDocxFile($template_file, $file_dest, $file_name, $search_replace_map, 'doc_logo_left', 'image2.jpeg')) {
      self::makeSureMetaDataInContext($references, $context);
      $context['results']['latest_file_destination'] = 'ssr_tmp' . DIRECTORY_SEPARATOR .  $file_dest;
      $context['results']['latest_file_name'] = $file_name . '.docx';
    }
  }

  public static function makeSureMetaDataInContext($references, &$context) {
    $context['results']['base_destination'] = $references['base_destination'];
    $context['results']['iup_round_name'] = $references['iup_round_name'];
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
    $file_name = str_replace(' ', '_', $results['iup_round_name']) . '_' . \Drupal::service('uuid')->generate() . '.zip';
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
      \Drupal::messenger()->addMessage(t('IUP file generation complete. Save the file in a secure location. This file will shortly be removed from server. Download it now form @link', ['@link' => $link]));

      $file_system->deleteRecursive($source_dir);
      return;
    };
    \Drupal::messenger()->addError(t('Something went wrong'));
  }
}
