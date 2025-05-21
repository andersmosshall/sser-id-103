(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.budgetTable = {
    attach: function (context, settings) {
      $(once('col-wrapper--processed', '.budget-table .col-wrapper', context)).each(function () {
        let $colWrapper = $(this);
        let $td = $(this).closest('td, th');

        if ($td.length) {
          const colspan = $colWrapper.attr('colspan');
          if (colspan) {
            $td.attr('colspan', colspan);
          }
        }
      });

      $(once('input--processed', '.budget-table input.col--real_sum')).each(function () {
        let $input = $(this);
        $input.change(function () {
          $input.closest('.budget-table').find('.col--real_sum.col--type--budget-sum, .col--result').each(function () {
            let $colWrapper = $(this);

            $colWrapper.removeClass('result--green');
            $colWrapper.addClass('result--red');
            $colWrapper.text(Drupal.t('Save to update'));

          });
        });

      });
    }
  };

})(jQuery, Drupal);
