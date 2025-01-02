<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_class_support\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_school_reports_class_support\Service\SsrClassServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the school class entity edit forms.
 */
final class SchoolClassForm extends ContentEntityForm {

  /**
   * The class service.
   */
  protected SsrClassServiceInterface $classService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->classService = $container->get('simple_school_reports_class_support.class_service');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\simple_school_reports_class_support\SchoolClassInterface $class */
    $class = parent::validateForm($form, $form_state);

    $class_name = $class->label();
    // Check for special characters that is not allowed as directory name since
    // class is used as directory name in some file generation.
    $blacklist = ['/', '\\', '?', '%', '*', ':', '|', '"', "'", '<', '>', '.'];
    if (str_replace($blacklist, '', $class_name) !== $class_name) {
      $form_state->setErrorByName('label', $this->t('The class name %name contains one or many characters (%invalid).', [
        '%name' => $class_name,
        '%invalid' => implode(', ', $blacklist),
      ]));
    }

    if ($class->id() && !$class->get('status')->value) {
      $student_uids = $this->classService->getStudentIdsByClassId($class->id());
      if (!empty($student_uids)) {
        $form_state->setErrorByName('status', $this->t('A class with students cannot be deactivated.'));
      }
    }

    return $class;
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
        $this->messenger()->addStatus($this->t('New school class %label has been created.', $message_args));
        $this->logger('simple_school_reports_class_support')->notice('New school class %label has been created.', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The school class %label has been updated.', $message_args));
        $this->logger('simple_school_reports_class_support')->notice('The school class %label has been updated.', $logger_args);
        break;

      default:
        throw new \LogicException('Could not save the entity.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl());

    return $result;
  }

}
