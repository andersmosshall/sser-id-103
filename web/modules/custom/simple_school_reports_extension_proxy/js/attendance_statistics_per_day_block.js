(function ($, Drupal) {
  'use strict';

  function createPieChart(attended, validAbsence, invalidAbsence, notReported, $svgWrapper) {
    // Convert inputs to floats and calculate the total
    attended = parseFloat(attended.toString());
    validAbsence = parseFloat(validAbsence.toString());
    invalidAbsence = parseFloat(invalidAbsence.toString());
    notReported = parseFloat(notReported.toString());

    const isNotReported = notReported === 100;

    if (notReported === 100) {
      attended = 0;
      validAbsence = 0;
      invalidAbsence = 0;
    }

    attended = Math.max(0.001, attended);
    validAbsence = Math.max(0.001, validAbsence);
    invalidAbsence = Math.max(0.001, invalidAbsence);
    notReported = Math.max(0.001, notReported);

    const attendedOrNotReported = attended + notReported;

    const total = attendedOrNotReported + validAbsence + invalidAbsence;

    if (total <= 0) {
      return
    }

    // Utility function to calculate end coordinates of an arc
    function polarToCartesian(centerX, centerY, radius, angleInDegrees) {
      const angleInRadians = (angleInDegrees - 90) * Math.PI / 180.0;
      return {
        x: centerX + (radius * Math.cos(angleInRadians)),
        y: centerY + (radius * Math.sin(angleInRadians))
      };
    }

    function createArcPath(centerX, centerY, radius, startAngle, endAngle) {
      const start = polarToCartesian(centerX, centerY, radius, endAngle);
      const end = polarToCartesian(centerX, centerY, radius, startAngle);
      const largeArcFlag = endAngle - startAngle <= 180 ? "0" : "1";

      return [
        "M", start.x, start.y,
        "A", radius, radius, 0, largeArcFlag, 0, end.x, end.y,
        "L", centerX, centerY,
        "Z"
      ].join(" ");
    }

    // Create the SVG container
    const svgNS = "http://www.w3.org/2000/svg";
    const svg = document.createElementNS(svgNS, "svg");
    svg.setAttribute("width", 200);
    svg.setAttribute("height", 200);
    svg.setAttribute("viewBox", "0 0 200 200");

    const centerX = 100, centerY = 100, radius = 80;
    let currentAngle = 0;

    // Create segments
    const segments = [
      { value: attendedOrNotReported, color: isNotReported ? "#978D8DBF" : "#2cc036bf" },
      { value: validAbsence, color: "#f9ab35" },
      { value: invalidAbsence, color: "#d72222bf" },
    ];

    segments.forEach(segment => {
      if (segment.value > 0) { // Skip segments with value 0
        const segmentAngle = (segment.value / total) * 360;
        const path = document.createElementNS(svgNS, "path");
        const startAngle = currentAngle;
        const endAngle = currentAngle + segmentAngle;

        path.setAttribute("d", createArcPath(centerX, centerY, radius, startAngle, endAngle));
        path.setAttribute("fill", segment.color);

        svg.appendChild(path);
        currentAngle += segmentAngle;
      }
    });

    $svgWrapper.append(svg);
  }

  Drupal.behaviors.ssrDIAttendingRequired = {
    attach: function (context, settings) {
      $(once('attendance-day-lesson-wrapper--processed', '.attendance-day-lesson-wrapper', context))
        .each(function () {
          const $svgWrapper = $(this).find('.attendance-day-lesson-svg');
          const $stats = $(this).find('.attendance-day-lesson-stat');

          if (!$svgWrapper.length || !$stats.length) {
            return;
          }

          let attended = $stats.data('attended') ?? 0;
          let validAbsence = $stats.data('valid-absence') ?? 0;
          let invalidAbsence = $stats.data('invalid-absence') ?? 0;
          let notReported = $stats.data('not-reported') ?? 0;

          createPieChart(attended, validAbsence, invalidAbsence, notReported, $svgWrapper);
        });
    }
  };

})(jQuery, Drupal);
