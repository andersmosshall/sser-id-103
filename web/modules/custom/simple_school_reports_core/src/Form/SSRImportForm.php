<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
use Drupal\simple_school_reports_core\UserFormAlter;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


class SSRImportForm extends FormBase {


  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;


  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    FileSystemInterface        $file_system
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file_system'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ssr_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['json'] = [
      '#title' => 'Import file',
      '#type' => 'managed_file',
      '#upload_location' => 'public://ssr_tmp',
      '#required' => TRUE,
      '#default_value' => NULL,
      '#upload_validators' => [
        'file_validate_extensions' => ['json'],
      ],
    ];

    $form['fake_mail_phone'] = [
      '#type' => 'checkbox',
      '#title' => 'Fake mail and phone',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $templates = [];
    /** @var \Drupal\file\FileStorageInterface $file_storage */
    $file_storage = $this->entityTypeManager->getStorage('file');
    $json_fid = $form_state->getValue('json');
    /** @var FileInterface $json_file */
    $json_file = $file_storage->load(current($json_fid));
    $content = file_get_contents($this->fileSystem->realpath($json_file->getFileUri()));
    $json = json_decode($content, TRUE);

    $fake_mail_phone = $form_state->getValue('fake_mail_phone', FALSE);

    // Setup batch.

    $batch = [
      'title' => $this->t('Import'),
      'init_message' => $this->t('Import'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'operations' => [],
      'finished' => [self::class, 'finished'],
    ];

    $init_operations = [];

    $user_operations_prio0 = [];
    $user_operations_prio1 = [];
    $user_operations_prio2 = [];
    $user_operations_prio3 = [];
    $user_operations_prio4 = [];

    $operations_prio1 = [];
    $operations_prio2 = [];
    $operations_prio3 = [];
    $operations_prio4 = [];
    $operations_prio5 = [];
    $operations_prio6 = [];

    // Import users.
    foreach ($json['user'] as $user_key => &$field_data_u) {
      if ($user_key == 4) {
        $field_data_u['field_last_name'] .= ' (R)';
      }

      if ($field_data_u['mail'] === 'anders@mosshall.se') {
        $field_data_u['mail'] = 'anders_r@mosshall.se';
      }

      $user_operations_prio1[] = [[self::class, 'createEntity'], [['user', $user_key], 'user', []]];
    }

    // Import caregivers.
    foreach ($json['email_map'] as $caregiver_mail => $field_data_c) {
      if (!isset($field_data_c['caregiver'])) {
        continue;
      }

      $field_data_c = $field_data_c['caregiver'];

      if ($field_data_c['mail'] === 'anders@mosshall.se') {
        $field_data_c['mail'] = 'anders_r@mosshall.se';
      }

      $user_operations_prio2[] = [[self::class, 'createEntity'], [['email_map', $caregiver_mail], 'user', []]];

      if (!empty($field_data_c['field_address'])) {
        $user_operations_prio0[] = [[self::class, 'createEntity'], [['email_map', $caregiver_mail, 'field_address'], 'paragraph', ['type' => 'address']]];
      }

      $json['email_map'][$caregiver_mail] = $field_data_c;
    }

    // Import students.
    foreach ($json['student'] as $student_key => &$field_data_s) {
      $user_operations_prio4[] = [[self::class, 'createEntity'], [['student', $student_key], 'user', []]];
      if (!empty($field_data_s['field_address'])) {
        $user_operations_prio3[] = [[self::class, 'createEntity'], [['student', $student_key, 'field_address'], 'paragraph', ['type' => 'address']]];
      }
    }

    // Import day_absence.
    foreach ($json['day_absence'] as $node_key => $field_data_da) {
      $operations_prio1[] = [[self::class, 'createEntity'], [['day_absence', $node_key], 'node', ['type' => 'day_absence']]];
    }

    // Import course.

    $banned_courses = [];
    foreach ($json['course'] as $node_key => $field_data_course) {
      if (empty($field_data_course['field_student'])) {
        $banned_courses[$node_key] = TRUE;
        continue;
      }
      $operations_prio1[] = [[self::class, 'createEntity'], [['course', $node_key], 'node', ['type' => 'course']]];
      $operations_prio6[] = [[self::class, 'resolveCoursePublished'], [['course', $node_key], 'node']];
    }

    // Import course attendance.
    foreach ($json['course_attendance_report'] as $node_key => &$field_data_att) {
      if (isset($banned_courses[$field_data_att['field_course']])) {
        continue;
      }

      $operations_prio2[] = [[self::class, 'createEntity'], [['course_attendance_report', $node_key], 'node', ['type' => 'course_attendance_report']]];

      $operations_prio5[] = [[self::class, 'updateEntityReference'], [['course_attendance_report', $node_key], ['course', $field_data_att['field_course']], 'node', 'field_course']];
      unset($field_data_att['field_course']);

      if (!empty($field_data_att['field_student_course_attendance'])) {
        foreach ($field_data_att['field_student_course_attendance'] as $a_key => $a_field_data) {
          $operations_prio1[] = [[self::class, 'createEntity'], [['course_attendance_report', $node_key, 'field_student_course_attendance', $a_key], 'paragraph', ['type' => 'student_course_attendance']]];
        }
      }
    }


    // Import grade rounds.
    foreach ($json['grade_round'] as $node_key => &$field_data_gr) {
      $operations_prio4[] = [[self::class, 'createEntity'], [['grade_round', $node_key], 'node', ['type' => 'grade_round']]];
      if (!empty($field_data_gr['field_student_groups'])) {
        foreach ($field_data_gr['field_student_groups'] as $g_key => &$g_field_data) {
          $operations_prio3[] = [[self::class, 'createEntity'], [['grade_round', $node_key, 'field_student_groups', $g_key], 'node', ['type' => 'grade_student_group']]];

          if (!empty($g_field_data['field_grade_subject'])) {
            foreach ($g_field_data['field_grade_subject'] as $s_key => &$s_field_data) {
              $operations_prio2[] = [[self::class, 'createEntity'], [['grade_round', $node_key, 'field_student_groups', $g_key, 'field_grade_subject', $s_key], 'node', ['type' => 'grade_subject']]];

              if (!empty($s_field_data['field_grade_registration'])) {
                foreach ($s_field_data['field_grade_registration'] as $r_key => &$r_field_data) {
                  $operations_prio5[] = [[self::class, 'updateEntityReference'], [['grade_round', $node_key, 'field_student_groups', $g_key, 'field_grade_subject', $s_key, 'field_grade_registration', $r_key], ['grade_round', $node_key], 'paragraph', 'field_grade_round']];
                  unset($r_field_data['field_grade_round']);
                  $operations_prio1[] = [[self::class, 'createEntity'], [['grade_round', $node_key, 'field_student_groups', $g_key, 'field_grade_subject', $s_key, 'field_grade_registration', $r_key], 'paragraph', ['type' => 'grade_registration']]];
                }
              }
            }
          }
        }
      }
    }


    // Import iup.
    foreach ($json['iup_round'] as $node_key => $field_data_iup_round) {
      $operations_prio1[] = [[self::class, 'createEntity'], [['iup_round', $node_key], 'node', ['type' => 'iup_round']]];
    }
    foreach ($json['iup'] as $node_key => &$field_data_iup) {
      $operations_prio5[] = [[self::class, 'updateEntityReference'], [['iup', $node_key], ['iup_round', $field_data_iup['field_iup_round']], 'node', 'field_iup_round']];
      unset($field_data_iup['field_iup_round']);
      $operations_prio3[] = [[self::class, 'createEntity'], [['iup', $node_key], 'node', ['type' => 'iup']]];

      if (!empty($field_data_iup['field_iup_goal_list'])) {
        foreach ($field_data_iup['field_iup_goal_list'] as $g_key => &$g_field_data_iup) {
          $operations_prio5[] = [[self::class, 'updateEntityReference'], [['iup', $node_key, 'field_iup_goal_list', $g_key], ['iup_round', $g_field_data_iup['field_iup_round']], 'node', 'field_iup_round']];
          unset($field_data_iup['field_iup_round']);
          $operations_prio2[] = [[self::class, 'createEntity'], [['iup', $node_key, 'field_iup_goal_list', $g_key], 'node', ['type' => 'iup_goal']]];
        }
      }
    }


    // Import written reviews.
    foreach ($json['written_reviews_round'] as $node_key => &$field_data_written_reviews_round) {
      $operations_prio3[] = [[self::class, 'createEntity'], [['written_reviews_round', $node_key], 'node', ['type' => 'written_reviews_round']]];
      if (!empty($field_data_written_reviews_round['field_written_reviews_subject'])) {
        foreach ($field_data_written_reviews_round['field_written_reviews_subject'] as $s_key => &$s_field_data) {
          $operations_prio2[] = [[self::class, 'createEntity'], [['written_reviews_round', $node_key, 'field_written_reviews_subject', $s_key], 'node', ['type' => 'written_reviews_subject_state']]];
          if (!empty($s_field_data['field_written_reviews'])) {
            foreach ($s_field_data['field_written_reviews'] as $r_key => &$r_field_data_wr) {
              $operations_prio5[] = [[self::class, 'updateEntityReference'], [['written_reviews_round', $node_key, 'field_written_reviews_subject', $s_key, 'field_written_reviews', $r_key], ['written_reviews_round', $node_key], 'paragraph', 'field_written_reviews_round']];
              unset($r_field_data_wr['field_written_reviews_round']);
              $operations_prio1[] = [[self::class, 'createEntity'], [['written_reviews_round', $node_key, 'field_written_reviews_subject', $s_key, 'field_written_reviews', $r_key], 'paragraph', ['type' => 'written_review']]];
            }
          }
        }
      }
    }

    foreach ($json['written_reviews'] as $node_key => &$field_data_wr) {
      $operations_prio5[] = [[self::class, 'updateEntityReference'], [['written_reviews', $node_key], ['written_reviews_round', $field_data_wr['field_written_reviews_round']], 'node', 'field_written_reviews_round']];
      unset($field_data_wr['field_written_reviews_round']);
      $operations_prio1[] = [[self::class, 'createEntity'], [['written_reviews', $node_key], 'node', ['type' => 'written_reviews']]];
    }

    $init_operations[] = [[self::class, 'init'], [$json, $fake_mail_phone]];
    $init_operations[] = [
      [self::class, 'handleSubjects'],
      [$json['subjects']],
    ];
    $init_operations[] = [
      [self::class, 'handleGradeReferences'],
      [],
    ];

    $operations_order = [$init_operations, $user_operations_prio0, $user_operations_prio1, $user_operations_prio2, $user_operations_prio3, $user_operations_prio4, $operations_prio1, $operations_prio2, $operations_prio3, $operations_prio4, $operations_prio5, $operations_prio6];

    foreach ($operations_order as $operations) {
      foreach ($operations as $operation) {
        $batch['operations'][] = $operation;
      }
    }

    if (!empty($batch['operations'])) {
      batch_set($batch);
    }
    else {
      \Drupal::messenger()
        ->addError(t('Something went wrong. Nothing to import.'));
    }
  }

  public static function init($json, $fake_mail_phone, &$context) {
    $context['results']['json'] = $json;
    $context['results']['fake_mail_phone'] = $fake_mail_phone;
    $context['results']['failed'] = FALSE;
  }

  public static function handleSubjects($subjects, &$context) {
    if (!empty($context['results']['failed'])) {
      return;
    }
    try {
      $vid = 'school_subject';
      /** @var \Drupal\taxonomy\TermStorageInterface $termStorage */
      $termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

      foreach ($subjects as $subject_key => $subject) {
        $query = $termStorage->getQuery()
          ->accessCheck(FALSE)
          ->condition('vid', $vid);
        $code = $subject['field_subject_code'] ?? NULL;
        if ($code) {
          $query->condition('field_subject_code', $code);
          $field_language_code = $subject['field_language_code'] ?? NULL;
          if ($field_language_code) {
            $query->condition('field_language_code', $field_language_code);
          }
        }
        else {
          $query->condition('name', $subject['name'] ?? '?');
        }
        $field_subject_specify = $subject['field_subject_specify'] ?? NULL;
        if ($field_subject_specify) {
          $query->condition('field_subject_specify', $field_subject_specify);
        }

        $id = current($query->execute());

        if (!$id) {
          /** @var \Drupal\taxonomy\TermInterface $term */
          $term = $termStorage->create([
            'name' => $subject['name'] ?? '?',
            'vid' => $vid,
            'langcode' => 'sv',
            'status' => 1,
          ]);

          foreach ($subject as $field => $value) {
            if ($term->hasField($field)) {
              $term->set($field, $value);
            }
          }

          $term->save();
          $id = $term->id();
        }

        $subject['id'] = $id;
        $subject['entity_type'] = 'taxonomy_term';
        $context['results']['json']['subjects'][$subject_key]['id'] = $id;
        $context['results']['json']['subjects'][$subject_key]['entity_type'] = 'taxonomy_term';
      }
    } catch (\Exception $e) {
      $context['results']['failed'] = TRUE;
    }
  }

  public static function handleGradeReferences(&$context) {
    if (!empty($context['results']['failed'])) {
      return;
    }
    try {
      $vids = ['af_grade_system', 'geg_grade_system'];

      /** @var \Drupal\taxonomy\TermStorageInterface $termStorage */
      $termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      foreach ($vids as $vid) {
        $grade_terms = $termStorage->loadTree($vid, 0, NULL, TRUE);
        /** @var \Drupal\taxonomy\TermInterface $grade_term */
        foreach ($grade_terms as $grade_term) {
          $context['results']['json']['grade'][$grade_term->label()]['id'] = $grade_term->id();
          $context['results']['json']['grade'][$grade_term->label()]['entity_type'] = 'taxonomy_term';
        }
      }
    } catch (\Exception $e) {
      $context['results']['failed'] = TRUE;
    }
  }


  public static function handleTargetReference($data) {
    if (empty($data['id'])) {
      throw new \RuntimeException('reference fail no id');
    }

    if (!empty($data['removed'])) {
      return [
        'target_id' => $data['id'],
      ];
    }

    try {
      $entity = \Drupal::entityTypeManager()
        ->getStorage($data['entity_type'])
        ->load($data['id']);
    }
    catch (\Exception $e) {
      throw new \RuntimeException('found bug error loading entity reference.');
    }

    if (!$entity) {
      throw new \RuntimeException('reference fail no entity');
    }



    return $entity;

  }

  public static function createEntity(array $source_map, string $entity_type, array $extra_fields, &$context) {
    try {
      $reference_field_map = [
        'field_student' => 'student',
        'field_students' => 'student',
        'field_caregiver' => 'email_map',
        'field_caregivers' => 'email_map',
        'field_mentor' => 'user',
        'field_mentors' => 'user',
        'field_teacher' => 'user',
        'field_teachers' => 'user',
        'field_principle' => 'user',
        'field_principles' => 'user',
        'field_subject' => 'subjects',
        'field_subjects' => 'subjects',
        'field_school_subject' => 'subjects',
        'field_school_subjects' => 'subjects',
        'field_course' => 'course',
        'field_courses' => 'course',
        'field_grade' => 'grade',
      ];
      if (!empty($context['results']['failed'])) {
        return;
      }

      $fields = &$context['results']['json'];
      foreach ($source_map as $key) {
        $fields = &$fields[$key];
      }

      // Abort if already processed.
      if (!empty($fields['id'])) {
        return;
      }

      foreach ($extra_fields as $field => $value) {
        $fields[$field] = $value;
      }

      $create_array = [];
      $create_array_fields = ['title', 'name', 'bundle', 'type'];
      foreach ($create_array_fields as $create_array_field) {
        if (isset($fields[$create_array_field])) {
          $create_array[$create_array_field] = $fields[$create_array_field];
          unset($fields[$create_array_field]);
        }
      }

      $entity = \Drupal::entityTypeManager()
        ->getStorage($entity_type)
        ->create($create_array);

      foreach ($fields as $field => $value) {
        if (!$entity->hasField($field)) {
          continue;
        }

        if ($value) {
          if (is_array($value)) {
            if (isset($value['format'])) {
              $entity->set($field, $value);
              continue;
            }

            if (!empty($value['id'])) {
              $entity->set($field, self::handleTargetReference($value));
              continue;
            }

            $value_to_set = [];
            foreach ($value as $sub_value) {
              if (is_array($sub_value)) {
                if (!empty($sub_value['id'])) {
                  $value_to_set[] = self::handleTargetReference($sub_value);
                }
                else {
                  throw new \RuntimeException('error setting field ' . $field);
                }
              }
              else {
                $check_value = self::handleReferenceFieldMap($field, $sub_value, $reference_field_map, $context['results']['json']);
                if ($check_value) {
                  $value_to_set[] = $check_value;
                }
              }
            }
            if (!empty($value_to_set)) {
              $entity->set($field, $value_to_set);
            }
          }
          else {
            $entity->set($field, self::handleReferenceFieldMap($field, $value, $reference_field_map, $context['results']['json']));
          }
        }
      }

      $email_map = [];
      if ($entity instanceof UserInterface) {
        $entity->set('name', UserFormAlter::resolveNameValue(NULL));
        $email = $entity->getEmail();
        if ($email) {
          $email_map[] = $email;
          // Sanity check email.
          if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strpos($email, '@example.com') !== FALSE) {
            $email = UserFormAlter::resolveMailValue(NULL);
            $email_map[] = $email;
            $entity->setEmail($email);
          }
        }
        else {
          $entity->setEmail(UserFormAlter::resolveMailValue(NULL));
        }

        if ($context['results']['fake_mail_phone']) {
          $last_name =  $entity->get('field_last_name')->value ?? '';
          $first_name = $entity->get('field_first_name')->value ?? '';
          $email = mb_strtolower($first_name) . '.' . mb_strtolower($last_name) . '_' . mt_rand(2,9999) . '@example.com';
          $email = filter_var($email, FILTER_SANITIZE_EMAIL);
          $entity->setEmail($email);

          $phone = '070' . mt_rand(1234567, 9999999);
          $entity->set('field_telephone_number', $phone);
        }
      }

      self::entitySave($entity, $fields);

      if (!empty($email_map)) {
        foreach ($email_map as $item) {
          $context['results']['json']['email_map'][$item] = $fields;
        }
      }

      $message = 'create ' . $entity_type;
      if (isset($extra_fields['type'])) {
        $message .= ' ' . $extra_fields['type'];
      }
      \Drupal::messenger()->addStatus($message);

    } catch (\Exception $e) {
      $context['results']['failed'] = TRUE;
    }
  }

  public static function handleReferenceFieldMap($field, $value, $reference_field_map, &$context) {
    if (!isset($reference_field_map[$field])) {
      return $value;
    }

    // Field grade can mean 2 things, but if it is numeric it's 'Årskurs'.
    if ($field === 'field_grade' && is_numeric($value)) {
      return $value;
    }

    if (!empty($context[$reference_field_map[$field]][$value]['id'])) {
      return self::handleTargetReference($context[$reference_field_map[$field]][$value]);
    }

    // Imitate removed students.
    if ($value < 0 && $reference_field_map[$field] === 'student') {
      $user = \Drupal::entityTypeManager()->getStorage('user')->create([
        'name' => $value,
      ]);
      $user->save();
      $context[$reference_field_map[$field]][$value]['id'] = $user->id();
      $context[$reference_field_map[$field]][$value]['removed'] = TRUE;
      $user->delete();
      return self::handleTargetReference($context[$reference_field_map[$field]][$value]);
    }

    \Drupal::messenger()->addWarning('missing reference field ' . $field . ' for value ' . $value);
    return NULL;
  }

  public static function updateEntityReference(array $source_map, array $reference_source_map, string $entity_type, string $field, &$context) {
    try {
      if (!empty($context['results']['failed'])) {
        return;
      }


      $fields = &$context['results']['json'];
      foreach ($source_map as $key) {
        $fields = &$fields[$key];
      }

      $reference_fields = &$context['results']['json'];
      foreach ($reference_source_map as $key) {
        $reference_fields = &$reference_fields[$key];
      }

      if (empty($fields['id']) || empty($reference_fields['id'])) {
        throw new \RuntimeException('id missing in fields or reference fields');
      }

      if ($fields['entity_type'] !== $entity_type) {
        throw new \RuntimeException('a bug is found, wrong entity type');
      }

      /** @var ContentEntityInterface $entity */
      $entity = \Drupal::entityTypeManager()
        ->getStorage($entity_type)
        ->load($fields['id']);

      if (!$entity || !$entity->hasField($field)) {
        throw new \RuntimeException('entity is missing');
      }

      $entity->set($field, self::handleTargetReference($reference_fields));
      self::entitySave($entity, $fields);

      $message = 'create reference ' . $field;
      \Drupal::messenger()->addStatus($message);
    } catch (\Exception $e) {
      $context['results']['failed'] = TRUE;
    }
  }

  public static function entitySave(ContentEntityInterface $entity, &$context) {
    $entity->set('langcode', 'sv');
    if ($entity->hasField('field_locked')) {
      $entity->set('field_locked', TRUE);
    }

    if ($entity->hasField('status')) {
      $entity->set('status', TRUE);
    }

    if ($entity->getEntityTypeId() !== 'user' && method_exists($entity, 'setNewRevision')) {
      $entity->setNewRevision(FALSE);
    }

    $entity->save();

    if (empty($context['id'])) {
      $context['id'] = $entity->id();
      $context['entity_type'] = $entity->getEntityTypeId();
      $context['entity_bundle'] = $entity->bundle();
    }

    if ($context['id'] == '1' && $context['entity_type'] == 'node') {
      \Drupal::messenger()->addWarning('Avoid creating node 1 permission bug may appear');
      throw new \RuntimeException();
    }
  }

  public static function resolveCoursePublished(array $source_map, string $entity_type, &$context) {
    try {
      if (!empty($context['results']['failed'])) {
        return;
      }

      $fields = &$context['results']['json'];
      foreach ($source_map as $key) {
        $fields = &$fields[$key];
      }

      if (empty($fields['id'])) {
        throw new \RuntimeException('id missing in course published resolver');
      }

      if ($fields['entity_type'] !== $entity_type) {
        throw new \RuntimeException('a bug is found, wrong entity type');
      }

      /** @var ContentEntityInterface $entity */
      $entity = \Drupal::entityTypeManager()
        ->getStorage($entity_type)
        ->load($fields['id']);

      if (!$entity || $entity->bundle() !== 'course') {
        throw new \RuntimeException('entity is missing');
      }

      $published_limit = new \DateTime();
      $published_limit->sub(new \DateInterval('P13M'));
      $published_limit_time = $published_limit->getTimestamp();

      $latest_report_id  = current(\Drupal::entityTypeManager()->getStorage($entity_type)
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'course_attendance_report')
        ->condition('field_course', $entity->id())
        ->sort('field_class_start', 'DESC')
        ->range(0,1)
        ->execute());

      if ($latest_report_id) {
        /** @var ContentEntityInterface $entity */
        $latest_report = \Drupal::entityTypeManager()
          ->getStorage($entity_type)
          ->load($latest_report_id);

        if ($latest_report && $latest_report->get('field_class_start')->value) {
          if ($latest_report->get('field_class_start')->value < $published_limit_time) {
            $entity->set('status', FALSE);
            $entity->setNewRevision(FALSE);
            $entity->save();
          }
        }
      }

    } catch (\Exception $e) {
      $context['results']['failed'] = TRUE;
    }
  }

  public
  static function finished($success, $results) {
    if (!$success || $results['failed'] === TRUE) {
      \Drupal::messenger()
        ->addError(t('Something went wrong. Please revert the database ASAP.'));
      return;
    }

    \Drupal::messenger()->addStatus(t('Import finished.'));
  }

}
