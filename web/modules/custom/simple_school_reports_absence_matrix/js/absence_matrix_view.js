(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.absenceMatrixView = {
    attach: function (context, settings) {
      $(once('view-absence-matrix--processed', '.view-absence-matrix table')).each(function () {
        let $table = $(this);
        let mondayTimestamp = $table.data('monday-timestamp');

        if (mondayTimestamp) {
          const dateList = [];

          let date = new Date();
          date.setTime(mondayTimestamp * 1000);

          const formatDate = (date) => {
            const formatted = date.toLocaleDateString('sv-SE', {
              weekday: 'short',
              day: 'numeric',
              month: 'numeric',
            });
            return formatted.charAt(0).toUpperCase() + formatted.slice(1)
          };

          const today = new Date();
          today.setHours(0,0,0,0);
          let todayIndex = null;

          dateList.push(formatDate(date));

          for (let i = 0; i < 7; i++) {
            date.setDate(date.getDate() + 1);
            if (date.getTime() == today.getTime()) {
              todayIndex = dateList.length;
            }
            dateList.push(formatDate(date));
          }

          $table.find('tr').each(function () {
            const $tr = $(this);
            let thIndex = 0;

            for (let i = 0; i < 7; i++) {
              if (!dateList[i]) {
                continue;
              }
              const classSuffix = i > 0 ? '-' + i : '';
              const classAdd = i === todayIndex ? 'current-date' : null;

              $tr.find('th.views-field-absence-matrix-info' + classSuffix).each(function () {
                $(this).text(dateList[i]);
                if (classAdd) {
                  $(this).addClass(classAdd);
                }
              });

              if (classAdd) {
                $tr.find('td.views-field-absence-matrix-info' + classSuffix).each(function () {
                  $(this).addClass(classAdd);
                });
              }
            }
          });
        }
      })
    }
  };

})(jQuery, Drupal);
