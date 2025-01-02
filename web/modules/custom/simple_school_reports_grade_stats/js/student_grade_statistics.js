(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.studentGradeStatistics = {
    attach: function (context, settings) {
      $(once('visible-columns-select--processed', '.student-grade-statistics .visible-columns-select input', context))
        .each(function () {
          let $checkbox = $(this);
          $checkbox.change(function () {
            const checked = $checkbox.prop('checked');
            const value = $checkbox.prop('value');
            if (value) {
              let displayVal = checked ? '' : 'none';
              $('.col--' + value).each(function () {
                let $div = $(this);
                $div.closest('td, th').css('display', displayVal);
              });
            }
          });

          $checkbox.prop('checked', true).change();
        });
    }
  };

})(jQuery, Drupal);
