<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\FileInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class FileTemplateService
 *
 * @package Drupal\simple_school_reports_core\Service
 */
interface FileTemplateServiceInterface {

  const WORD_CHECKBOX_CHECKED = '</w:t></w:r><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="20"/></w:rPr><w:sym w:font="Wingdings" w:char="F0FD"/></w:r><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="20"/></w:rPr><w:t xml:space="preserve">';
  const WORD_CHECKBOX_UNCHECKED = '</w:t></w:r><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="20"/></w:rPr><w:sym w:font="Wingdings" w:char="F0A8"/></w:r><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="20"/></w:rPr><w:t xml:space="preserve">';

  const WORD_WHITESPACE = '</w:t></w:r><w:r><w:rPr><w:rFonts w:cs="Garamond"/><w:sz w:val="18"/><w:szCs w:val="20"/></w:rPr><w:t xml:space="preserve"> ';
  const WORD_NEW_LINE = '</w:t></w:r></w:p><w:p w:rsidR="006C6940" w:rsidRDefault="006C6940" w:rsidP="008748DC"><w:pPr><w:tabs><w:tab w:val="left" w:pos="7450"/></w:tabs><w:rPr><w:rFonts w:cs="Garamond"/><w:sz w:val="18"/><w:szCs w:val="20"/></w:rPr></w:pPr><w:r><w:rPr><w:rFonts w:cs="Garamond"/><w:sz w:val="18"/><w:szCs w:val="20"/></w:rPr><w:t>';
  const WORD_BOLD_START = '</w:t></w:r><w:r w:rsidRPr="009032F7"><w:rPr><w:rFonts w:cs="Garamond"/><w:b/><w:sz w:val="18"/><w:szCs w:val="20"/></w:rPr><w:t>';
  const WORD_BOLD_END = '</w:t></w:r><w:r><w:rPr><w:rFonts w:cs="Garamond"/><w:sz w:val="18"/><w:szCs w:val="20"/></w:rPr><w:t xml:space="preserve">';
  const WORD_ITALIC_START = '</w:t></w:r><w:r w:rsidRPr="009032F7"><w:rPr><w:rFonts w:cs="Garamond"/><w:i/><w:sz w:val="18"/><w:szCs w:val="20"/></w:rPr><w:t>';
  const WORD_ITALIC_END = self::WORD_BOLD_END;
  const WORD_AND = '&amp;';

  /**
   * Get file template(s).
   *
   * NOTE: it does not take local template files into account.
   *
   * @param string|null $key
   *
   * @return array|FileInterface|null
   */
  public function getFileTemplate(?string $key = NULL);

  /**
   * Get file real path.
   *
   * NOTE: it does take local template files into account.
   *
   * @param string $key
   */
  public function getFileTemplateRealPath(string $key): ?string;

  /**
   * @param array $template
   */
  public function setFileTemplate(array $template);

  /**
   * @param string $template_file
   * @param string $destination
   * @param string $file_name
   * @param array $search_replace_map
   *
   * @return bool
   */
  public function generateDocxFile(string $template_file_type, string $destination, string $file_name, array $search_replace_map = [], ?string $doc_logo = NULL, string $doc_logo_name = 'image1.jpeg'): bool;

  /**
   * @param \Drupal\file\FileInterface $template_file
   * @param string $destination
   * @param string $file_name
   * @param array $search_replace_map
   *
   * @return bool
   */
  public function generateXlsxFile(string $template_file_type, string $destination, string $file_name, array $search_replace_map = [], ?string $doc_logo = NULL, string $doc_logo_name = 'image1.jpeg'): bool;

  /**
   * @param string $source_dir
   * @param string $destination_dir
   * @param string $file_name
   *
   * @return bool
   */
  public function doZip(string $source_dir, string $destination_dir, string $file_name, bool $destination_prepared = FALSE): bool;

  /**
   * @param string $text
   * @param string $format
   *
   * @return string
   */
  public function handleFormattedWordText(string $text, string $format = 'wordsupported_format'): string;

  public function sanitizeFileName(string $file_name, bool $prepare_directory = TRUE, bool $lower_case = TRUE): string;
}
