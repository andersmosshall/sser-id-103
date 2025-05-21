(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.gradeStatisticsNode = {
    attach: function (context, settings) {
      $(once('improvedselect_filter--processed', '.grade-statistics-form input.improvedselect_filter', context))
        .each(function () {
          let $input = $(this);
          setTimeout(() => {
            $input.attr('placeholder', 'Ange namn för att filtrera på betygsomgång');
          }, 0);
        });
    }
  };

})(jQuery, Drupal);
