(function ($, Drupal) {

  Drupal.behaviors.ssrUserBulkForm = {
    attach(context, settings) {
      // Select the inner-most table in case of nested tables.
      $(once(
        'table-select-ssr-user-bulk-form',
        $(context).find('th.select-all').closest('table'),
      )).each(function () {
        let $table = $(this);

        if ($table.data('total-uid-list').length) {
          setTimeout(() => {
            $table.find('th.select-all input[type="checkbox"]').on('click', (event) => {
              if ($table.data('total-uid-list').length) {
                let uids = $table.data('total-uid-list');
                let count = 0;
                if (Array.isArray(uids)) {
                  count = uids.length;
                }

                if (count && $(event.target).is('input[type="checkbox"]')) {
                  let status = null;
                  let value  = '';

                  if (event.target.checked) {
                    status = Drupal.formatPlural(
                      count,
                      '1 item selected',
                      '@count items selected',
                    );
                    value = JSON.stringify(uids);
                  }

                  setTimeout(() => {
                    $('input[data-override-uid-list]').val(value);
                    $('div[data-drupal-views-bulk-actions-status]').text(status);
                  }, 500);
                }
              }

            });
          }, 500);
        }

      });
    },
  };


})(jQuery, Drupal);
