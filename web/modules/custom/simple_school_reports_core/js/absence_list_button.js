(function ($, Drupal, debounce) {
  Drupal.behaviors.ssrAbsenceListButton = {
    attach(context, settings) {
      $(context).find('.absence-list-button').each(function () {
        var $button = $(this);

        function resolveButtonVisibility() {
          var $absenceList = $(context).find('#block-ssr-base-views-block-registered-absence-today');
          if ($absenceList.length && $absenceList.first().position().top > window.innerHeight) {
            $button.css('display', '');
          }
          else {
            $button.css('display', 'none');
          }
        }

        resolveButtonVisibility();
        // Redo this on window resize
        $(window).on('resize', debounce(resolveButtonVisibility, 100));
      });
    },
  };
})(jQuery, Drupal, Drupal.debounce);

