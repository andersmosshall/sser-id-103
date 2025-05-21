<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\file\FileInterface;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Drupal\simple_school_reports_core\Pnum;
use Drupal\simple_school_reports_core\SchoolSubjectHelper;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserStorageInterface;
use phpDocumentor\Reflection\DocBlock\Tags\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form to generate ssn key.
 */
class GenerateSsnKeyForm extends ConfirmFormBase {


  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\simple_school_reports_core\Pnum
   */
  protected $pnum;


  public function __construct(Connection $connection, SessionInterface $session, EntityTypeManagerInterface $entity_type_manager, Pnum $pnum) {
    $this->connection = $connection;
    $this->session = $session;
    $this->entityTypeManager = $entity_type_manager;
    $this->pnum = $pnum;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('session'),
      $container->get('entity_type.manager'),
      $container->get('simple_school_reports_core.pnum')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'generate_ssn_key_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Generate ssn key form');
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
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['old_ssn_key'] = [
      '#title' => t('Old personal number key'),
      '#description' => t('If included any valid personal number in supplied file will be inserted in the new personal number key.'),
      '#type' => 'managed_file',
      '#upload_location' => 'public://tmp',
      '#default_value' => NULL,
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = $this->connection->select('user__roles', 'r');
    $query->innerJoin('user__field_first_name', 'fn', 'fn.entity_id = r.entity_id');
    $query->innerJoin('user__field_last_name', 'ln', 'ln.entity_id = r.entity_id');
    $query->leftJoin('user__field_grade', 'g', 'g.entity_id = r.entity_id');
    $query->condition('r.roles_target_id', 'student')
      ->fields('fn',['field_first_name_value'])
      ->fields('ln',['field_last_name_value'])
      ->fields('r',['entity_id'])
      ->orderBy('g.field_grade_value')
      ->orderBy('fn.field_first_name_value')
      ->orderBy('ln.field_last_name_value');
    $results = $query->execute();
    $ssn_map = [];

    /** @var \Drupal\file\FileInterface $file */
    if (!empty($form_state->getValue('old_ssn_key')) && $file = $this->entityTypeManager->getStorage('file')->load($form_state->getValue('old_ssn_key')[0])) {
      $ssn_map = self::parseSsnCsv($file, TRUE);
      $file->delete();
    }

    $csv_data = [];

    foreach ($results as $result) {
      if (!$result->entity_id) {
        continue;
      }

      $csv_data_row = [];
      $csv_data_row[] = $result->entity_id;
      $csv_data_row[] = $result->field_first_name_value ?? '';
      $csv_data_row[] = $result->field_last_name_value ?? '';
      $csv_data_row[] = isset($ssn_map[$result->entity_id]) ? $ssn_map[$result->entity_id] : '';
      $csv_data[] = $csv_data_row;
    }


    $id = 'ssn-key-csv-file';

    $file_content = self::createSsnCSV($csv_data);
    $file_name = 'personnummer_nyckel.csv';

    $this->session->set('file-gen--' . $id, [
      'content' => $file_content,
      'file_name' => $file_name,
    ]);

    $path = Url::fromRoute('simple_school_reports_core.file-generator', ['id' => $id])->toString();
    $link = Markup::create('<a href="' . $path . '" target="_blank">' . t('here') . '</a>');

    $this->messenger()->addMessage($this->t('Personal number key file generated, download it @link', ['@link' => $link]));
  }


  public static function createSsnCSV(array $data, $delimiter = ';'): string {
    $rows = [];
    $label_row = ['ElevId (Ändra ej!)', 'Förnamn', 'Efternamn', 'Personnummer (AAMMDD-NNNN)'];
    $rows[] = $label_row;
    $rows = array_merge($rows, $data);

    $lines = [];
    foreach ($rows as $row_data) {
      foreach ($row_data as &$row_item) {
        $row_item = '"' . $row_item . '"';
      }
      $lines[] = implode($delimiter, $row_data);
    }

    return implode(PHP_EOL, $lines);
  }

  public static function parseSsnCsv(FileInterface $file, bool $skip_invalid = FALSE, array $delimiters = [',', ';']): array {
    $map = [];

    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $path = $file_system->realpath($file->getFileUri());

    if ($path) {
      $content = file_get_contents($path);

      if (is_string($content)) {
        /** @var Pnum $pnum */
        $pnum = NULL;
        if ($skip_invalid) {
          $pnum = \Drupal::service('simple_school_reports_core.pnum');
        }

        $lines = explode(PHP_EOL, $content);
        if (is_array($lines) && !empty($lines)) {
          foreach ($lines as $line) {
            $row_data = NULL;
            foreach ($delimiters as $delimiter) {
              $row_data = explode($delimiter, $line);
              if (is_array($row_data) && count($row_data) === 4) {
                break;
              }
            }

            if (is_array($row_data) && count($row_data) === 4) {
              $student_uid = str_replace('"', '', $row_data[0]);
              if ($student_uid) {
                $ssn = str_replace('"', '', $row_data[3]);
                if ($ssn) {
                  if ($skip_invalid) {
                    $normalised_ssn = $pnum->normalizeIfValid($ssn);
                    if ($normalised_ssn) {
                      $map[$student_uid] = $normalised_ssn;
                    }
                  }
                  else {
                    $map[$student_uid] = $ssn;
                  }
                }
              }
            }
          }
        }
      }
    }

    return $map;
  }

}
