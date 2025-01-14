(function ($, Drupal, once) {
  'use strict';

  function handleBlur($switch) {
    let doBlur = !$switch.is(':checked');

    let whiteListClasses = [
      '.field--name-field-first-name',
      '.field--name-field-middle-name',
      '.field--name-field-last-name',
      '.field-roles',
      '.field--name-field-mentor',
      '.field--name-field-grade',
      '.field--name-field-class',
      '.field--name-field-notes',
      '.field--name-field-extra-adaptations',
      '.field--name-field-invalid-absence',
      '.field--name-field-special-diet',
      '.field--name-field-allow-login',
    ];

    $('.profile > .field').each(function () {
      let $field = $(this);
      if (!doBlur) {
        $field.removeClass('blur-wrapper');
        return;
      }

      let fieldDoNotBlur = whiteListClasses.find(function (selector) {
        return $field.is(selector);
      });

      if (fieldDoNotBlur) {
        $field.removeClass('blur-wrapper');
      }
      else {
        $field.addClass('blur-wrapper');
      }
    });
  }

  Drupal.behaviors.ssrBlurSecretFields = {
    attach: function (context, settings) {
      $(once('blur-secret-fields-switch--processed', 'input.blur-secret-fields-switch')).each(function () {
        let $switch = $(this);
        handleBlur($switch);
        $switch.change(function () {
          handleBlur($switch);
        });
      })
    }
  };

})(jQuery, Drupal, once);
