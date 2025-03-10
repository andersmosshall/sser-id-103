<?php

namespace Drupal\simple_school_reports_maillog\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Controller for MailCountController.
 */
class MailCountController extends ControllerBase {

  public function mailCountPageAccess(AccountInterface $account): AccessResult {
    return ssr_views_permission_maillog_active($account);
  }

}
