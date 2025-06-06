<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\FileInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class MessageTemplateService
 *
 * @package Drupal\simple_school_reports_core\Service
 */
class FileTemplateService implements FileTemplateServiceInterface, EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * @var UuidInterface
   */
  protected $uuid;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var string|null
   */
  protected $ssrTemplateBasePath = NULL;


  public function __construct(
    StateInterface $state,
    EntityTypeManagerInterface $entity_type_manager,
    FileSystemInterface $file_system,
    UuidInterface $uuid,
    ModuleHandlerInterface $module_handler
  ) {
    $this->state = $state;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->uuid = $uuid;
    $this->moduleHandler = $module_handler;
  }


  public function getFileTemplate(?string $key = NULL) {
    $fids = $this->state->get('ssr_file_templates', []);
    $fids += [
      'student_grade_term' => NULL,
      'student_grade_final' => NULL,
      'student_group_grade' => NULL,
      'teacher_grade_sign' => NULL,
      'written_reviews' => NULL,
      'iup' => NULL,
      'dnp_empty' => NULL,
      'doc_logo_left' => NULL,
      'doc_logo_center' => NULL,
      'doc_logo_right' => NULL,
      'logo_header' => NULL,
    ];

    $file_storage = $this->entityTypeManager->getStorage('file');

    if ($key) {
      /** @var FileInterface $file */
      if (isset($fids[$key]) && $file = $file_storage->load($fids[$key])) {
        return $file;
      }
      return NULL;
    }

    $return = [];

    foreach ($fids as $key => $fid) {
      if ($fid) {
        $return[$key] = $file_storage->load($fid);
      }
      else {
        $return[$key] = NULL;
      }
    }

    return $return;
  }

  public function getFileTemplateRealPath(string $key): ?string {
    // Any uploaded files from the template take precedence.
    $file = $this->getFileTemplate($key);
    if ($file instanceof FileInterface) {
      return $this->fileSystem->realpath($file->getFileUri());
    }

    if (!$this->ssrTemplateBasePath) {
      $this->ssrTemplateBasePath = $this->moduleHandler->getModuleDirectories()['simple_school_reports_core'] . DIRECTORY_SEPARATOR . 'ssr-file-templates' . DIRECTORY_SEPARATOR;
    }
    $template_file_path_base = $this->ssrTemplateBasePath;

    // Check for local template files.
    $local_templates = [
      'student_grade_term' => $template_file_path_base . 'Terminsbetyg.docx',
      'student_grade_final' => $template_file_path_base . 'Slutbetyg.docx',
      'student_group_grade' => $template_file_path_base . 'Betygskatalog.xlsx',
      'teacher_grade_sign' => $template_file_path_base . 'Signering.xlsx',
      'written_reviews' => $template_file_path_base . 'SO.docx',
      'iup' => $template_file_path_base . 'IUP.docx',
      'dnp_empty' => $template_file_path_base . 'dnp-empty-1.5.2.xlsx',
    ];

    if (isset($local_templates[$key]) && file_exists($local_templates[$key])) {
      return $local_templates[$key];
    }

    return NULL;
  }

  public function setFileTemplate(array $template) {
    $this->state->set('ssr_file_templates', $template);
  }

  public function generateDocxFile(string $template_file_type, string $destination, string $file_name, array $search_replace_map = [], ?string $doc_logo = NULL, string $doc_logo_name = 'image1.jpeg'): bool {
    $destination = 'ssr_tmp' . DIRECTORY_SEPARATOR . $destination;

    $template_file_path = $this->getFileTemplateRealPath($template_file_type);
    if (!$template_file_path) {
      return FALSE;
    }

    $working_dir = 'public://ssr_tmp' . DIRECTORY_SEPARATOR . $this->uuid->generate() . DIRECTORY_SEPARATOR;
    $working_path = $working_dir;
    $this->fileSystem->prepareDirectory($working_dir, FileSystemInterface::CREATE_DIRECTORY);
    $working_path = $this->fileSystem->realpath($working_path);
    $zip = new \ZipArchive();
    $res = $zip->open($template_file_path);
    if ($res === TRUE) {
      $zip->extractTo($working_path);
      $zip->close();
    }

    // Copy the logo to the media directory.
    if ($doc_logo) {
      if ($doc_logo_file_path = $this->getFileTemplateRealPath($doc_logo)) {
        $logo_path = $working_path . DIRECTORY_SEPARATOR . 'word' . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $doc_logo_name;
        $this->fileSystem->copy($doc_logo_file_path, $logo_path, FileExists::Replace);
      }
    }

    $content_path = $working_path . DIRECTORY_SEPARATOR . 'word' . DIRECTORY_SEPARATOR . 'document.xml';
    $content = file_get_contents($content_path);
    if ($content !== FALSE) {
      $content = str_replace(array_keys($search_replace_map), array_values($search_replace_map), $content);
      file_put_contents($content_path, $content);
    }
    $result = $this->doZip($working_path, $destination, $file_name . '.docx');
    $this->fileSystem->deleteRecursive($working_path);
    return $result;
  }

  public function generateXlsxFile(string $template_file_type, string $destination, string $file_name, array $search_replace_map = [], ?string $doc_logo = NULL, string $doc_logo_name = 'image1.jpeg'): bool {
    $destination = 'ssr_tmp' . DIRECTORY_SEPARATOR . $destination;

    $template_file_path = $this->getFileTemplateRealPath($template_file_type);
    if (!$template_file_path) {
      return FALSE;
    }

    $working_dir = 'public://ssr_tmp' . DIRECTORY_SEPARATOR . $this->uuid->generate() . DIRECTORY_SEPARATOR;
    $working_path = $this->fileSystem->getDestinationFilename($working_dir, FileSystemInterface::CREATE_DIRECTORY);
    $working_path = $this->fileSystem->realpath($working_path);
    $zip = new \ZipArchive();
    $res = $zip->open($this->fileSystem->realpath($template_file_path));
    if ($res === TRUE) {
      $zip->extractTo($working_path);
      $zip->close();
    }

    // Copy the logo to the media directory.
    if ($doc_logo) {
      if ($doc_logo_file_path = $this->getFileTemplateRealPath($doc_logo)) {
        $logo_path = $working_path . DIRECTORY_SEPARATOR . 'xl' . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $doc_logo_name;
        $this->fileSystem->copy($doc_logo_file_path, $logo_path, FileExists::Replace);
      }
    }

    $content_path = $working_path . DIRECTORY_SEPARATOR . 'xl' . DIRECTORY_SEPARATOR . 'sharedStrings.xml';
    $content = file_get_contents($content_path);
    if ($content !== FALSE) {
      $content = str_replace(array_keys($search_replace_map), array_values($search_replace_map), $content);
      file_put_contents($content_path, $content);
    }
    $result = $this->doZip($working_path, $destination, $file_name . '.xlsx');
    $this->fileSystem->deleteRecursive($working_path);
    return $result;
  }

  public function doZip(string $source_dir, string $destination_dir, string $file_name): bool {
    $destination = 'public://'  . $destination_dir;
    $this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY);
    $destination = $this->fileSystem->realpath($destination) . DIRECTORY_SEPARATOR;
    $final_destination = $destination . DIRECTORY_SEPARATOR . $file_name;

    $zip = new \ZipArchive;
    $zip->open($final_destination, \ZipArchive::CREATE);
    $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source_dir), \RecursiveIteratorIterator::SELF_FIRST);
    foreach ($files as $file) {
      $file = str_replace('\\', DIRECTORY_SEPARATOR, $file);

      // Ignore "." and ".." folders
      if( in_array(substr($file, strrpos($file, DIRECTORY_SEPARATOR)+1), array('.', '..')) )
        continue;

      $file = realpath($file);

      if (is_dir($file) === true)
      {
        $zip->addEmptyDir(str_replace($source_dir . DIRECTORY_SEPARATOR, '', $file . DIRECTORY_SEPARATOR));
      }
      else if (is_file($file) === true)
      {
        $zip->addFromString(str_replace($source_dir . DIRECTORY_SEPARATOR, '', $file), file_get_contents($file));
      }
    }
    return $zip->close();
  }


  public function handleFormattedWordText(string $text, string $format = 'wordsupported_format'): string {
    $text = (string) check_markup($text, $format);
    $text = trim($text);

    $normalise_map = [];
    $normalise_map['</p>'] = '<br>';
    $normalise_map['<br>'] = '<br>';
    $normalise_map['<br/>'] = '<br>';
    $normalise_map['<br />'] = '<br>';
    $text = str_replace(array_keys($normalise_map), array_values($normalise_map), $text);

    // Remove all attributes from p tags.
    $text = preg_replace('/\<p[^\>]*\>/', '<p>', $text);

    // Remove leading line breaks.
    $start_pattern = '/^\<br\>/';
    while (preg_match($start_pattern, $text)) {
      $text = preg_replace($start_pattern, '', $text);
      $text = trim($text);
    }

    // Remove ending line breaks.
    $end_pattern = '/\<br\>$/';
    while (preg_match($end_pattern, $text)) {
      $text = preg_replace($end_pattern, '', $text);
      $text = trim($text);
    }

    $search_replace_map = [];
    $search_replace_map['<p>'] = '';
    $search_replace_map['<br>'] = self::WORD_NEW_LINE;

    // Bold
    $search_replace_map[' <strong>'] = self::WORD_WHITESPACE . self::WORD_BOLD_START;
    $search_replace_map['</strong> '] = self::WORD_BOLD_END . self::WORD_WHITESPACE;
    $search_replace_map['<strong>'] = self::WORD_BOLD_START;
    $search_replace_map['</strong>'] = self::WORD_BOLD_END;

    // Italic
    $search_replace_map[' <em>'] = self::WORD_WHITESPACE . self::WORD_ITALIC_START;
    $search_replace_map['</em> '] = self::WORD_ITALIC_END . self::WORD_WHITESPACE;
    $search_replace_map['<em>'] = self::WORD_ITALIC_START;
    $search_replace_map['</em>'] = self::WORD_ITALIC_END;

    $search_replace_map['&nbsp;'] = self::WORD_WHITESPACE;

    $text = str_replace(array_keys($search_replace_map), array_values($search_replace_map), $text);
    return $text;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // This event must be run last to ensure the filename obeys the security
    // rules.
    $events[FileUploadSanitizeNameEvent::class][] = 'sanitizeName';
    return $events;
  }

  /**
   * Sanitizes the upload's filename to make it not include forbidden
   * characters.
   *
   * @param \Drupal\Core\File\Event\FileUploadSanitizeNameEvent $event
   *   File upload sanitize name event.
   */
  public function sanitizeName(FileUploadSanitizeNameEvent $event): void {
    $filename = $event->getFilename();
    $filename = preg_replace(
      '~
        [<>:"/\\|?*]|            # file system reserved https://en.wikipedia.org/wiki/Filename#Reserved_characters_and_words
        [\x00-\x1F]|             # control characters http://msdn.microsoft.com/en-us/library/windows/desktop/aa365247%28v=vs.85%29.aspx
        [\x7F\xA0\xAD]|          # non-printing characters DEL, NO-BREAK SPACE, SOFT HYPHEN
        [#\[\]@!$&\'()+,;=]|     # URI reserved https://tools.ietf.org/html/rfc3986#section-2.2
        [{}^\~`]                 # URL unsafe characters https://www.ietf.org/rfc/rfc1738.txt
        ~x',
      '-', $filename);
    $filename = str_replace(' ', '_', $filename);

    if ($filename !== $event->getFilename()) {
      $event->setFilename($filename)->setSecurityRename();
    }
  }

}
