(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.AbsenceDayStudentStatistics = {
    attach: function (context, settings) {
      let chartIds = drupalSettings && drupalSettings.ssrGraphData && drupalSettings.ssrGraphData.absence_day_student_statistics ? Object.keys(drupalSettings.ssrGraphData.absence_day_student_statistics ) : [];

      if (chartIds.length) {
        chartIds.forEach((chartId) => {
          $(once('chart' + chartId + '--processed', '#' + chartId, context)).each(function () {

            let stackedValues = [2];
            drupalSettings.ssrGraphData.absence_day_student_statistics[chartId].datasets[0].data.forEach((item, key) => {
              let value = item + drupalSettings.ssrGraphData.absence_day_student_statistics[chartId].datasets[1].data[key];
              stackedValues.push(Math.ceil(value) + 2);
            });

            const ctx = document.getElementById(chartId).getContext('2d');
            const invalidAbsenceStudentChart = new Chart(ctx, {
              type: 'bar',
              data: drupalSettings.ssrGraphData.absence_day_student_statistics[chartId],
              options: {
                maintainAspectRatio: false,
                scales: {
                  yAxes: {
                    beginAtZero: true,
                    suggestedMax: Math.max(...stackedValues),
                    stacked: true,
                  },
                  xAxes: {
                    stacked: true,
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
