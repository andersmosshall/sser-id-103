(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.ssrDIAttendingRequired = {
    attach: function (context, settings) {
      $(once('di-attending-select-widget--processed', '.field--widget-ssr-di-attending-select-widget', context))
        .each(function () {
          let $fieldset = $(this).find('fieldset.fieldset--group');
          if ($fieldset.length) {
            const required = $fieldset.data('required-values');
            required.forEach((value) => {
              let $input = $fieldset.find(`input[value="${value}"]`);
              if ($input.length) {
                $input.attr('required', 'required');
                $input.prop("checked", true);
              }
            });
          }
        });
    }
  };

})(jQuery, Drupal);
