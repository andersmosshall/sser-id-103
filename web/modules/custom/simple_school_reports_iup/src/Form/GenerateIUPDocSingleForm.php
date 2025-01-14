<?php

namespace Drupal\simple_school_reports_iup\Form;

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
use Drupal\simple_school_reports_iup\IUPFormAlter;
use Drupal\simple_school_reports_reviews\WrittenReviewRoundFormAlter;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for generating single IUP document.
 */
class GenerateIUPDocSingleForm extends ConfirmFormBase {

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
    return 'generate_iup_docs_single_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Generate IUP document');
  }

  public function getCancelRoute() {
    return '<front>';
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
    if (!$node || $node->bundle() !== 'iup') {
      throw new AccessDeniedHttpException();
    }

    $form['iup_nid'] = [
      '#type' => 'value',
      '#value' => $node->id(),
    ];

    $form = parent::buildForm($form, $form_state);

    $form['#title'] = t('Generate iup document') . ' - ' . $node->label();

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

    /** @var NodeInterface $iup */
    $iup = $node_storage->load($form_state->getValue('iup_nid'));

    if (!$iup) {
      $this->messenger()->addError($this->t('Something went wrong'));
      return;
    }

    /** @var NodeInterface $iup_round */
    $iup_round = current($iup->get('field_iup_round')->referencedEntities());

    if (!$iup_round) {
      $this->messenger()->addError($this->t('Something went wrong'));
      return;
    }

    $references = GenerateIUPDocMultipleForm::generateReferences($iup_round, $this->uuid->generate());

    $batch['operations'][] = [[GenerateIUPDocMultipleForm::class, 'generateIUPDoc'], [$iup->id(), $references]];

    if (!empty($batch['operations'])) {
      $batch['finished'] = [self::class, 'finished'];
      batch_set($batch);
    }
    else {
      $this->messenger()->addWarning($this->t('Something went wrong'));
    }
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
    \Drupal::messenger()->addMessage(t('IUP file generation complete. Save the file in a secure location. This file will shortly be removed from server. Download it now form @link', ['@link' => $link]));
  }
}
