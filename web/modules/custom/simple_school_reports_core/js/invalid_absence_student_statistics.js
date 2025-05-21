(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.invalidAbsenceStudentStatistics = {
    attach: function (context, settings) {
      let chartIds = drupalSettings && drupalSettings.ssrGraphData && drupalSettings.ssrGraphData.invalid_absence_student_statistics ? Object.keys(drupalSettings.ssrGraphData.invalid_absence_student_statistics ) : [];

      if (chartIds.length) {
        chartIds.forEach((chartId) => {
          $(once('chart' + chartId + '--processed', '#' + chartId, context)).each(function () {
            const ctx = document.getElementById(chartId).getContext('2d');
            const invalidAbsenceStudentChart = new Chart(ctx, {
              type: 'bar',
              data: drupalSettings.ssrGraphData.invalid_absence_student_statistics[chartId],
              options: {
                maintainAspectRatio: false,
                scales: {
                  yAxes: {
                    beginAtZero: true,
                    suggestedMax: Math.max(...drupalSettings.ssrGraphData.invalid_absence_student_statistics[chartId].datasets[0].data) + 20,
                  },
                }
              }
            });
          });
        });
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
