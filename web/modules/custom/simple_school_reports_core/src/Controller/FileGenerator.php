<?php

namespace Drupal\simple_school_reports_core\Controller;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\simple_school_reports_core\Service\TermServiceInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller for UserPageController.
 */
class FileGenerator extends ControllerBase {

  /**
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    SessionInterface $session
  ) {
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('session'),
    );
  }

  public function generate(string $id) {
    $file_data = $this->session->get('file-gen--' . $id);
    if (empty($file_data) || empty($file_data['content']) || empty($file_data['file_name'])) {
      throw new AccessDeniedHttpException();
    }

    $content = $file_data['content'];
    $file_name = $file_data['file_name'];
    $response = new Response();
    $response->headers->set('Content-Type', 'application/csv');
    $response->headers->set('Content-Disposition', 'attachment;filename="' . $file_name . '"');
    $response->setContent($content);
    return $response;

  }

}
