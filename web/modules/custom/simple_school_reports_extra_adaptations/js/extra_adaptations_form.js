(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.extraAdaptationsForm = {
    attach: function (context, settings) {
      $(once('improvedselect_filter--processed', '.form-item--field-school-subjects input.improvedselect_filter', context))
        .each(function () {
          let $input = $(this);
          setTimeout(() => {
            $input.attr('placeholder', 'Ange namn för att filtrera på skolämne');
          }, 0);
        });
    }
  };

})(jQuery, Drupal);
