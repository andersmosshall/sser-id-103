(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.ChildCareGraph = {
    attach: function (context, settings) {
      $(once('child-care-overview-graph-container--processed', '.child-care-overview-graph-container', context)).each(function () {
        const data = $(this).data('graph-data');
        if (!Array.isArray(data)) {
          return;
        }

        const includeCheckIn = !!$(this).data('include-check-in');

        const processedData = {
          labels: data.map(item => item.label),
          datasets: [],
        };
        if (includeCheckIn) {

          const nowTs = Math.floor(Date.now() / 1000) + 15 * 60 + 1;

          processedData.datasets.push({
            label: Drupal.t('Checked in'),
            data: data.map(item => {
              const checkedInTs = item.from ?? null;
              if (checkedInTs === null) {
                return null;
              }
              if (checkedInTs > nowTs) {
                return null;
              }

              return item.checkedIn;
            }),
            fill: false,
            borderColor: 'rgb(100 223 0)',
            backgroundColor: 'rgb(100 223 0)',
            tension: 0.1,
            pointStyle: 'cross',
          });
        }

        processedData.datasets.push({
          label: Drupal.t('Child care needs'),
            data: data.map(item => item.needs),
          fill: false,
          borderColor: 'rgba(0, 60, 197, 0.75)',
          backgroundColor: 'rgba(0, 60, 197, 0.75)',
          tension: 0.1,
        });
        processedData.datasets.push({
          label: Drupal.t('Child care offers'),
            data: data.map(item => item.offers),
          fill: false,
          borderColor: 'rgb(108 108 108)',
          backgroundColor: 'rgb(108 108 108)',
          tension: 0.1,
          pointStyle: 'cross',
        });

        const maxValues = [Math.max(...data.map(item => item.needs)), Math.max(...data.map(item => item.offers)), Math.max(...data.map(item => item.checkedIn ?? 0)), 1];
        const chartId = 'child-care-overview-graph';
        const ctx = document.getElementById(chartId).getContext('2d');


        const suggestedMax = (Math.max(...maxValues)) * 1.1;

        const childCareOverviewChart = new Chart(ctx, {
          type: 'line',
          data: processedData,
          options: {
            scales: {
              yAxes: {
                beginAtZero: true,
                suggestedMax: suggestedMax,
                ticks: {
                  beginAtZero: true,
                  callback: function(value) {if (value % 1 === 0) {return value;}}
                }
              },
              xAxes: {
              },
            }
          }
        });
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
