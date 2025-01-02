(function ($, Drupal) {
  'use strict';

  function getParameterByName(name, url = window.location.href) {
    name = name.replace(/[\[\]]/g, '\\$&');
    var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
      results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
  }

  Drupal.behaviors.ssrPrint = {
    attach: function (context, settings) {
      $(document).ready(function() {
        let print = getParameterByName('print');
        if (print === '1') {
          window.print();
        }
      });

      $(once('action--ssr-print--processed', '.action--ssr-print', context))
        .each(function () {
          let $button = $(this);
          $button.attr('href', '#');
          $button.click(function () {
            window.print();
          });
        });
    }
  };

})(jQuery, Drupal);
