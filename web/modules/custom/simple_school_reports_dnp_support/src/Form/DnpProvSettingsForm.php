<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_dnp_support\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_school_reports_dnp_support\Service\DnpSupportServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Form controller for the dnp provisioning settings entity edit forms.
 */
final class DnpProvSettingsForm extends ContentEntityForm {

  /**
   * The DNP support service.
   */
  protected DnpSupportServiceInterface $dnpSupportService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->dnpSupportService = $container->get('simple_school_reports_dnp_support.dnp_support_service');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $parent_form = parent::buildForm($form, $form_state);

    $test_options = $this->dnpSupportService->getDnpTestOptions();
    if (empty($test_options)) {
      throw new AccessDeniedHttpException();
    }
    $entity = $this->getEntity();
    if ($entity->isNew()) {
      $step = $form_state->get('step') ?? 1;
      if ($step === 1) {
        $form = [];
        $form['tests_to_create'] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('Tests', [], ['context' => 'assessment']),
          '#options' => $test_options,
        ];

        $populate_teachers_options = [
          'yes' => $this->t('Yes'),
          'no' => $this->t('No'),
        ];
        $form['populate_teachers'] = [
          '#type' => 'select',
          '#title' => $this->t('Populate teachers for assessment'),
          '#description' => $this->t('If checked, teachers will be populated to each test by analyse of courses in Simple School Reports.'),
          '#options' => $populate_teachers_options,
          '#default_value' => 'yes',
        ];

        $form['actions'] = $parent_form['actions'];
        $form['actions']['submit']['#value'] = $this->t('Next');
        $form['actions']['submit']['#submit'] = [[$this, 'step1Submit']];
        return $form;
      }
    }

    return $parent_form;
  }

  /**
   * {@inheritdoc}
   */
  public function step1Submit(array &$form, FormStateInterface $form_state) {
    $form_state->set('step', 2);

    $test_ids = array_keys(array_filter($form_state->getValue('tests_to_create', [])));
    $populate_teachers = $form_state->getValue('populate_teachers') === 'yes';

    $tests = [];

    foreach ($test_ids as $test_id) {
      $dnp_prov_test_settings = $this->entityTypeManager->getStorage('dnp_prov_test_settings')->create([
        'langcode' => 'sv',
        'bundle' => 'default',
        'status' => TRUE,
        'test' => $test_id,
      ]);

      if ($populate_teachers) {
        $user_storage = $this->entityTypeManager->getStorage('user');
        $allowed_teachers = array_values($user_storage->getQuery()->accessCheck(FALSE)->condition('status', 1)->execute());

        $student_uids = $this->dnpSupportService->getStudentUidsForTest($test_id);
        $subject_code = $this->dnpSupportService->getSubjectFromDnpTestOption($test_id);
        $subject_tids = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
          ->accessCheck(FALSE)
          ->condition('vid', 'school_subject')
          ->condition('field_subject_code_new', $subject_code)
          ->execute();

        if (!empty($subject_tids) && !empty($student_uids)) {
          $courses = $this->entityTypeManager->getStorage('node')
            ->loadByProperties([
              'type' => 'course',
              'field_school_subject' => array_values($subject_tids),
              'field_student' => $student_uids,
              'status' => 1,
            ]);

          $teachers = [];
          foreach ($courses as $course) {
            $teacher_uids = array_column($course->field_teacher->getValue(), 'target_id');
            foreach ($teacher_uids as $teacher_uid) {
              if (in_array($teacher_uid, $allowed_teachers, TRUE)) {
                $teachers[$teacher_uid] = $teacher_uid;
              }
            }
          }

          $teachers_values = [];
          foreach ($teachers as $teacher_uid) {
            $teachers_values[] = ['target_id' => $teacher_uid];
          }
          $dnp_prov_test_settings->set('teachers', $teachers_values);
        }
      }
      try {
        $dnp_prov_test_settings->save();
      }
      catch (\Exception $e) {
        $this->messenger()->addError($this->t('An error occurred while creating the dnp provisioning settings.'));
        $this->logger('simple_school_reports_dnp_support')->error('An error occurred while creating the dnp provisioning settings: @message', ['@message' => $e->getMessage()]);
        return;
      }
      $tests[] = $dnp_prov_test_settings;
    }

    $entity = $this->getEntity();
    $entity->set('tests', $tests);

    $guaranteed_staff_values = [];

    $principle_uids = $this->entityTypeManager->getStorage('user')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('roles', 'principle')
      ->execute();
    foreach ($principle_uids as $principle_uid) {
      $guaranteed_staff_values[] = ['target_id' => $principle_uid];
    }

    $entity->set('guaranteed_staff', $guaranteed_staff_values);
    $entity->save();
    $request = $this->getRequest();

    $query = $request->query->all();
    $request->query->remove('destination');

    $form_state->setRedirect('entity.dnp_prov_settings.edit_form', ['dnp_prov_settings' => $entity->id()], ['query' => $query]);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $message_args = ['%label' => $this->entity->toLink()->toString()];
    $logger_args = [
      '%label' => $this->entity->label(),
      'link' => $this->entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New dnp provisioning settings %label has been created.', $message_args));
        $this->logger('simple_school_reports_dnp_support')->notice('New dnp provisioning settings %label has been created.', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The dnp provisioning settings %label has been updated.', $message_args));
        $this->logger('simple_school_reports_dnp_support')->notice('The dnp provisioning settings %label has been updated.', $logger_args);
        break;

      default:
        throw new \LogicException('Could not save the entity.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl());

    return $result;
  }

}
