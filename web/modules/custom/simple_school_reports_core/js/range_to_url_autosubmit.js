(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.rangeToUrlAutoSubmit = {
    attach: function (context, settings) {
      $(once('range-to-url-form--processed', '.range-to-url-form')).each(function () {
        const urlParams = new URLSearchParams(window.location.search);
        const from = urlParams.get('from');
        const to = urlParams.get('to');
        if (!from || !to) {
          $(this).submit();
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
