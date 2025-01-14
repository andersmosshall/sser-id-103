(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.GradeStatisticsCharts = {
    attach: function (context, settings) {
      let chartIds = drupalSettings && drupalSettings.gradeStatisticsGraphData && drupalSettings.gradeStatisticsGraphData.grade_statistics_graph ? Object.keys(drupalSettings.gradeStatisticsGraphData.grade_statistics_graph ) : [];

      if (chartIds.length) {
        chartIds.forEach((chartId) => {
          $(once('chart' + chartId + '--processed', '#' + chartId, context)).each(function () {

            const ctx = document.getElementById(chartId).getContext('2d');
            const gradeStatisticsChart = new Chart(ctx, {
              type: 'bar',
              data: drupalSettings.gradeStatisticsGraphData.grade_statistics_graph[chartId],
              options: {
                maintainAspectRatio: false,
                scales: {
                  yAxes: {
                    beginAtZero: true,
                    suggestedMax: 100,
                    title: {
                      text: 'Andel (%)',
                      display: true,
                    },
                  },
                },
                plugins: {
                  tooltip: {
                    callbacks: {
                      label: function (context) {
                        let label = context.dataset.label || '';

                        if (label) {
                          label += ': ';
                        }
                        if (context.parsed.y !== null) {
                          label += context.parsed.y + ' %';
                        }
                        return label;
                      },
                      afterLabel: function (context) {
                        if (context.dataIndex !== undefined) {
                          let rawData = context.dataset && context.dataset.rawData && context.dataset.rawData[context.dataIndex] || undefined;
                          if (rawData !== undefined) {
                            return '(' + rawData + ' st)';
                          }
                        }
                        return undefined;
                      }
                    }
                  }
                }
              }
            });
          });
        });
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
