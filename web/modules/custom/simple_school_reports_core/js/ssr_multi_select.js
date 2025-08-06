(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.ssrMultiSelect = {
    attach: function (context, settings) {
      setTimeout(() => {
        $(once('ssr-multi-select--processed', '.ssr-multi-select', context))
          .each(function () {
            let $select = $(this);
            let filterPlaceholder = $select.data('filter-placeholder') ?? '';
            let $input = $select.closest('.form-item').find('input.improvedselect_filter');
            $input.attr('placeholder', filterPlaceholder);
          });
      }, 0);
    }
  };
})(jQuery, Drupal);
