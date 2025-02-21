(function ($, Drupal) {

  Drupal.behaviors.ssrBulkForm = {
    attach(context, settings) {
      // Select the inner-most table in case of nested tables.
      $(once(
        'table-select-ssr-bulk-form',
        $(context).find('th.select-all').closest('table'),
      )).each(function () {
        let $table = $(this);

        if ($table.data('total-id-list').length) {
          setTimeout(() => {
            $table.find('th.select-all input[type="checkbox"]').on('click', (event) => {
              if ($table.data('total-id-list').length) {
                let ids = $table.data('total-id-list');
                let count = 0;
                if (Array.isArray(ids)) {
                  count = ids.length;
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
                    value = JSON.stringify(ids);
                  }

                  setTimeout(() => {
                    $('input[data-override-id-list]').val(value);
                    $('div[data-drupal-views-bulk-actions-status]').text(status);
                  }, 500);
                }
              }
            });

            // Clear the override input if any checkbox is unchecked.
            $table.find('tr > td input[type="checkbox"]').on('click', (event) => {
              if ($(event.target).is('input[type="checkbox"]') && !event.target.checked) {
                $('input[data-override-id-list]').val('');
              }
            });
          }, 500);
        }

      });
    },
  };


})(jQuery, Drupal);
