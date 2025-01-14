<?php

namespace Drupal\simple_school_reports_dnp_provisioning\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\simple_school_reports_dnp_support\DnpProvisioningInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller for DnpProvisioningController.
 */
class DnpProvisioningController extends ControllerBase {

  public function buildTableTab(DnpProvisioningInterface $dnp_provisioning, string $sheet) {
    $cache = new CacheableMetadata();
    $cache->setCacheMaxAge(0);
    $build = $dnp_provisioning->getTableRenderArray($sheet);
    $cache->applyTo($build);
    return $build;
  }

  public function downloadXlsx(DnpProvisioningInterface $dnp_provisioning) {
    if (!$dnp_provisioning->get('downloaded')->value) {
      $dnp_provisioning->set('downloaded', TRUE);
      $dnp_provisioning->save();
    }

    $filename = $dnp_provisioning->generateFileName(TRUE);
    $spreadsheet = $dnp_provisioning->makeXlsxFile();
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

    $response = new StreamedResponse(function () use ($writer) {
      $writer->save('php://output');
    });
    $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
    $response->headers->addCacheControlDirective('no-cache');
    $response->headers->addCacheControlDirective('no-store');
    $response->headers->addCacheControlDirective('must-revalidate');
    $response->setMaxAge(0);
    return $response;
  }

}
