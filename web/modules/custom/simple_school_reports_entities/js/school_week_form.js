(function ($, Drupal, once) {
  'use strict';

  function handleHideGroups($checkbox, $form) {
    const show = !$checkbox.is(':checked');
    for (let i = 1; i <= 7; i++) {
      if (show) {
        $form.find(`.school-week-day-wrapper-${i}`).show();
      }
      else {
        $form.find(`.school-week-day-wrapper-${i}`).hide();
      }
    }
  }

  Drupal.behaviors.ssrSchoolWeekForm = {
    attach: function (context, settings) {
      $(once('calculate-from-schema-checkbox--processed', '.form-type--checkbox input')).each(function () {
        const $checkbox = $(this);


        if (!$checkbox.attr('name').includes('calculate_from_schema')) {
          return;
        }
        const $wrappingForm = $checkbox.closest('form, .ief-form');

        // Handle group visibility.
        handleHideGroups($checkbox, $wrappingForm);
        $checkbox.change(function () {
          handleHideGroups($checkbox, $wrappingForm);
        });
      })
    }
  };

})(jQuery, Drupal, once);
