<?php

namespace Drupal\simple_school_reports_core\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller for StartPageController.
 */
class EntityFormModeController extends ControllerBase {

  public function addStudentEntityForm() {
    $student = $this->entityTypeManager()->getStorage('user')->create(['roles' => ['student']]);

    return $this->entityFormBuilder()->getForm($student, 'student');

  }

}
