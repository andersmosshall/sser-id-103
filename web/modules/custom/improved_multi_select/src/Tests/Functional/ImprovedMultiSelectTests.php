<?php

namespace Drupal\improved_multi_select\Tests\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Class ImprovedMultiSelectTests.
 *
 * Improved Multi Select Tests.
 *
 * @group Improved Multi Select
 *
 * @package Drupal\improved_multi_select\Tests
 */
class ImprovedMultiSelectTests extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['improved_multi_select'];

  /**
   * Test improved_multi_select_load_selectors() function.
   */
  public function testImsLoadSelectors() {
    $replace_all = FALSE;
    $selectors = [];
    $jquery_selectors = improved_multi_select_load_selectors($replace_all, $selectors);
    $this->assertSame($jquery_selectors, ['select[multiple]']);
    $replace_all = TRUE;
    $jquery_selectors = improved_multi_select_load_selectors($replace_all, $selectors);
    $this->assertSame($jquery_selectors, ['select[multiple]']);
    $selectors = ['test_selector'];
    $jquery_selectors = improved_multi_select_load_selectors($replace_all, $selectors);
    $this->assertSame($jquery_selectors, ['select[multiple]']);
    $replace_all = FALSE;
    $jquery_selectors = improved_multi_select_load_selectors($replace_all, $selectors);
    $this->assertSame($jquery_selectors, ['test_selector']);
  }

  /**
   * Test if Drupal loads with module enabled.
   */
  public function testIfDrupalLoads() {
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
    $user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/config/user-interface/ims');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalLogout();
  }

}
