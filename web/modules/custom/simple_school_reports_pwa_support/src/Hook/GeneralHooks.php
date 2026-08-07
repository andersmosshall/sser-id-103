<?php

namespace Drupal\simple_school_reports_pwa_support\Hook;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\image\Entity\ImageStyle;
use Drupal\simple_school_reports_core\Service\FileTemplateServiceInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * General hook implementations.
 */
class GeneralHooks {

  use StringTranslationTrait;

  protected FileTemplateServiceInterface $fileTemplateService;

  /**
   * Constructs a new GeneralHooks object.
   */
  public function __construct(
    protected AccountInterface $currentUser,
    protected TimeInterface $time,
    protected RouteMatchInterface $routeMatch,
    protected ModuleHandlerInterface $moduleHandler,
    protected RequestStack $requestStack,
  ) {
    $this->fileTemplateService = \Drupal::service('simple_school_reports_core.file_template_service');
  }

  protected function usePwa(): bool {
    return $this->moduleHandler->moduleExists('simple_school_reports_pwa');
  }

  protected function isIos(): bool {
    $request = $this->requestStack->getCurrentRequest();
    $user_agent = $request->headers->get('User-Agent', '');
    return (bool) preg_match('/(iPhone|iPod|iPad)/i', $user_agent);
  }

  protected function getIcons(): array {
    $file = $this->fileTemplateService->getFileTemplate('logo_shortcut');
    if (!$file) {
      return [];
    }

    $file_uri = $file->getFileUri();

    $image_style_512 = ImageStyle::load('512_square');
    $image_style_192 = ImageStyle::load('192_square');
    $image_style_144 = ImageStyle::load('144_square');

    $iconSrc = $image_style_512 ? $image_style_512->buildUrl($file_uri) : $file->createFileUrl();
    $iconSmallSrc = $image_style_192 ? $image_style_192->buildUrl($file_uri) : $file->createFileUrl();
    $iconVerySmallSrc = $image_style_144 ? $image_style_144->buildUrl($file_uri) : $file->createFileUrl();

    $httpHost = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost();

    return [
      0 => [
        'src' =>  $iconSrc,
        'sizes' => '512x512',
        'type' => 'image/png',
        'purpose' => 'any',
      ],
      1 => [
        'src' => $iconSmallSrc,
        'sizes' => '192x192',
        'type' => 'image/png',
        'purpose' => 'any',
      ],
      2 => [
        'src' => $iconVerySmallSrc,
        'sizes' => '144x144',
        'type' => 'image/png',
        'purpose' => 'any',
      ],
    ];

  }

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    if ($this->routeMatch->getRouteName() !== 'user.login') {
      return;
    }
    if (!$this->usePwa()) {
      return;
    }

    $icons = $this->getIcons();
    if (!empty($icons)) {
      foreach ($icons as $icon) {
        if (empty($icon['src'])) {
          continue;
        }

        $size = $icon['sizes'];

        // Add standard mobile/browser icon
        $attachments['#attached']['html_head'][] = [
          [
            '#type' => 'html_tag',
            '#tag' => 'link',
            '#attributes' => [
              'rel' => 'icon',
              'type' => $icon['type'],
              'sizes' => $size,
              'href' => $icon['src'],
            ],
          ],
          'shortcut_icon_' . $size,
        ];

        // Add iOS apple-touch-icon
        $attachments['#attached']['html_head'][] = [
          [
            '#type' => 'html_tag',
            '#tag' => 'link',
            '#attributes' => [
              'rel' => 'apple-touch-icon',
              'sizes' => $size,
              'href' => $icon['src'],
            ],
          ],
          'apple_touch_icon_' . $size,
        ];
      }

    }

    $attachments['#cache']['contexts'][] = 'user_agent:ios';
  }

  /**
   * Implements hook_page_attachments_alter().
   */
  #[Hook('page_attachments_alter')]
  public function pageAttachmentsAlter(array &$attachments): void {
    if ($this->routeMatch->getRouteName() !== 'user.login') {
      return;
    }
    if ($this->usePwa() && !$this->isIos()) {
      return;
    }
    if (empty($attachments['#attached']) || empty($attachments['#attached']['html_head'])) {
      return;
    }

    foreach ($attachments['#attached']['html_head'] as $key => $head_element) {
      // The second element in the array is the unique identifier string ('manifest').
      if (isset($head_element[1]) && $head_element[1] === 'manifest') {
        unset($attachments['#attached']['html_head'][$key]);
      }
    }

    if (in_array('pwa_service_worker/serviceworker', $attachments['#attached']['library'])) {
      $index = array_search('pwa_service_worker/serviceworker', $attachments['#attached']['library']);
      unset($attachments['#attached']['library'][$index]);
      unset($attachments['#attached']['drupalSettings']['pwa_service_worker']);
    }
  }

  /**
   * Implements hook_pwa_manifest_alter().
   */
  #[Hook('pwa_manifest_alter')]
  public function pwaManifestAlter(array &$data): void {
    if (!$this->usePwa()) {
      return;
    }
    $icons = $this->getIcons();
    if (!empty($icons)) {
      $data['icons'] = $this->getIcons();
    }
    $school_name = Settings::get('ssr_school_name');
    if ($school_name) {
      $data['name'] = $school_name . ' - Simple School Reports';
      $data['short_name'] = $school_name . ' - SSR';
    }
  }
}
