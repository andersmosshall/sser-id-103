<?php

namespace Drupal\simple_school_reports_skolon_export\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_school_reports_core\Form\ExportMultipleUsersForm;
use Drupal\simple_school_reports_entities\SSRLookupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a confirmation form for adding date range to url.
 */
class ExportUsersFromListForm extends ExportMultipleUsersForm {

  protected EntityTypeManagerInterface $entityTypeManager;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?array $accounts = NULL, ?string $method = NULL, SSRLookupInterface $ssr_lookup = null) {
    $accounts = [];

    $uids_json = $ssr_lookup->get('type')->value === 'skolon_export_list'
      ? $ssr_lookup->get('meta')->value
      : NULL;

    if (!empty($uids_json)) {
      $uids = Json::decode($uids_json);
      if (is_array($uids)) {
        $accounts = $this->entityTypeManager->getStorage('user')->loadMultiple($uids);
      }
    }

    if (empty($accounts)) {
      throw new NotFoundHttpException();
    }
    return parent::buildForm($form, $form_state, $accounts, 'simple_school_reports_skolon_export.export_users_skolon');
  }
}
