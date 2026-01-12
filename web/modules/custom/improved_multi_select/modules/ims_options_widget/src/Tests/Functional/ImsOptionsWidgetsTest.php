<?php

namespace Drupal\ims_options_widget\Tests\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field\Functional\FieldTestBase;

/**
 * Tests the IMS Options widgets.
 *
 * @group Improved Multi Select
 *
 * @package Drupal\ims_options_widget\Testss
 */
class ImsOptionsWidgetsTest extends FieldTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'options',
    'entity_test',
    'options_test',
    'taxonomy',
    'field_ui',
    'ims_options_widget',
  ];

  /**
   * A field storage with cardinality 2 to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $card2;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Field storage with cardinality 2.
    $this->card2 = FieldStorageConfig::create([
      'field_name' => 'card_2',
      'entity_type' => 'entity_test',
      'type' => 'list_integer',
      'cardinality' => 2,
      'settings' => [
        'allowed_values' => [
          // Make sure that 0 works as an option.
          0 => 'Zero',
          1 => 'One',
          // Make sure that option text is properly sanitized.
          2 => 'Some <script>dangerous</script> & unescaped <strong>markup</strong>',
        ],
      ],
    ]);
    $this->card2->save();

    // Create a web user.
    $this->drupalLogin($this->drupalCreateUser(['view test entity', 'administer entity_test content']));
  }

  /**
   * Tests the 'ims_options_select' widget (multiple select).
   */
  public function testSelectListMultiple() {
    // Create an instance of the 'multiple values' field.
    $field = FieldConfig::create([
      'field_storage' => $this->card2,
      'bundle' => 'entity_test',
    ]);
    $field->save();
    \Drupal::service('entity_display.repository')->getFormDisplay('entity_test', 'entity_test')
      ->setComponent($this->card2->getName(), [
        'type' => 'ims_options_select',
      ])
      ->save();

    // Create an entity.
    $entity = EntityTest::create([
      'user_id' => 1,
      'name' => $this->randomMachineName(),
    ]);
    $entity->save();
    $entity_init = clone $entity;

    // Display form: with no field data, nothing is selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertTrue($this->assertSession()->optionExists('card_2', '_none')->isSelected());
    $this->assertEmpty($this->assertSession()->optionExists('card_2', 0)->isSelected());
    $this->assertEmpty($this->assertSession()->optionExists('card_2', 1)->isSelected());
    $this->assertEmpty($this->assertSession()->optionExists('card_2', 2)->isSelected());
    $this->assertSession()->responseContains('Some dangerous &amp; unescaped markup');

    // Submit form: select first and third options.
    $edit = ['card_2[]' => [0 => 0, 2 => 2]];
    $this->submitForm($edit, $this->t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', [0, 2]);

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertTrue($this->assertSession()->optionExists('card_2', 0)->isSelected());
    $this->assertEmpty($this->assertSession()->optionExists('card_2', 1)->isSelected());
    $this->assertTrue($this->assertSession()->optionExists('card_2', 2)->isSelected());
    // Display form: check that the selected options are on the top.
    $option_elements = $this->xpath('//select[@id="edit-card-2"]/option');
    $options = [];
    $expected = [0, 2, '_none', 1];
    foreach ($option_elements as $option_element) {
      $options[] = $option_element->getValue();
    }
    $this->assertEquals($expected, $options);

    // Submit form: select only first option.
    $edit = ['card_2[]' => [0 => 0]];
    $this->submitForm($edit, $this->t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', [0]);

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertTrue($this->assertSession()->optionExists('card_2', 0)->isSelected());
    $this->assertEmpty($this->assertSession()->optionExists('card_2', 1)->isSelected());
    $this->assertEmpty($this->assertSession()->optionExists('card_2', 2)->isSelected());

    // Display form: check that the selected options are on the top.
    $option_elements = $this->xpath('//select[@id="edit-card-2"]/option');
    $options = [];
    $expected = [0, '_none', 1, 2];
    foreach ($option_elements as $option_element) {
      $options[] = $option_element->getValue();
    }
    $this->assertEquals($expected, $options);

    // Submit form: select the three options while the field accepts only 2.
    $edit = ['card_2[]' => [0 => 0, 1 => 1, 2 => 2]];
    $this->submitForm($edit, $this->t('Save'));
    $this->assertSession()->pageTextContains('this field cannot hold more than 2 values');

    // Submit form: uncheck all options.
    $edit = ['card_2[]' => []];
    $this->submitForm($edit, $this->t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', []);

    // Display form: check that the selected options from previous submission
    // are on the top.
    $option_elements = $this->xpath('//select[@id="edit-card-2"]/option');
    $options = [];
    $expected = ['_none', 0, 1, 2];
    foreach ($option_elements as $option_element) {
      $options[] = $option_element->getValue();
    }
    $this->assertEquals($expected, $options);

    // Test the 'None' option.
    // Check that the 'none' option has no effect if actual options are selected
    // as well.
    $edit = ['card_2[]' => ['_none' => '_none', 0 => 0]];
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->submitForm($edit, $this->t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', [0]);

    // Display form: check that the selected options are on the top.
    $option_elements = $this->xpath('//select[@id="edit-card-2"]/option');
    $options = [];
    $expected = [0, '_none', 1, 2];
    foreach ($option_elements as $option_element) {
      $options[] = $option_element->getValue();
    }
    $this->assertEquals($expected, $options);

    // Check that selecting the 'none' option empties the field.
    $edit = ['card_2[]' => ['_none' => '_none']];
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->submitForm($edit, $this->t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', []);

    // Display form: check that the selected options are on the top.
    $option_elements = $this->xpath('//select[@id="edit-card-2"]/option');
    $options = [];
    $expected = ['_none', 0, 1, 2];
    foreach ($option_elements as $option_element) {
      $options[] = $option_element->getValue();
    }
    $this->assertEquals($expected, $options);

    // A required select list does not have an empty key.
    $field->setRequired(TRUE);
    $field->save();
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertEmpty($this->xpath('//select[@id=:id]//option[@value=""]', [':id' => 'edit-card-2']), 'A required select list does not have an empty key.');

  }

}
