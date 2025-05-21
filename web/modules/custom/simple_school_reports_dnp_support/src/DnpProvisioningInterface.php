<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_dnp_support;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Provides an interface defining a dnp provisioning entity type.
 */
interface DnpProvisioningInterface extends DnpProvisioningConstantsInterface, ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  public const DNP_IDS_ONLY_IMPORTS = 1;
  public const DNP_IDS_ONLY_REMOVE = 2;
  public const DNP_IDS_ALL = 3;

  /**
   * @param string|null $prefix
   * @param $process
   *
   * @return string
   */
  public function generateFileName(bool $process = FALSE): string;

  /**
   * Parse source.
   *
   * @return array
   *   The parsed src data.
   */
  public function parseSrc(): array;

  /**
   * @param \Drupal\simple_school_reports_dnp_support\DnpSourceDataInterface $src
   *
   * @return self
   *   Return self.
   */
  public function createSrcData(DnpSourceDataInterface $src): self;

  /**
   * @param string $sheet
   *
   * @return array
   */
  public function getTableRenderArray(string $sheet): array;

  /**
   * @param string $sheet
   *
   * @return array
   */
  public function getFileMapData(string $sheet): array;

  /**
   * @param string $sheet
   * @param int $option
   *
   * @return array
   */
  public function getIds(string $sheet, int $option = self::DNP_IDS_ONLY_IMPORTS): array;

  /**
   * @param string $sheet
   * @param int $option
   *
   * @return array
   */
  public function getUids(string $sheet, int $option = self::DNP_IDS_ONLY_IMPORTS): array;

  /**
   * @param string $sheet
   * @param int $option
   *
   * @return int
   */
  public function getRowCount(string $sheet, int $option = self::DNP_IDS_ONLY_IMPORTS): int;

  /**
   * @return string[]
   */
  public function getWarnings(): array;

  /**
   * @return \PhpOffice\PhpSpreadsheet\Spreadsheet
   */
  public function makeXlsxFile(): Spreadsheet;

}
