(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.ssrDetailsToggler = {
    attach: function (context, settings) {
      $(once('ssr-details-toggle--processed', '.ssr-details-toggle')).click(function () {
        const $toggler = $(this);
        const $targetSelector = $toggler.data('toggle-selector');
        if (!$targetSelector) {
          return;
        }
        $($targetSelector).each(function () {
          const $target = $(this);
          if ($target.hasClass('show-details')) {
            $target.removeClass('show-details');
            $toggler.text(Drupal.t('Show details'));
          }
          else {
            $target.addClass('show-details');
            $toggler.text(Drupal.t('Hide details'));
          }
        });
      });
    }
  };

})(jQuery, Drupal);
