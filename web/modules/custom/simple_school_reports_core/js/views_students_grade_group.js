(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.viewsStudentsGradeGroup = {
    attach: function (context, settings) {
      $(once('students-group--processed', '.view.view-id-students table')).each(function () {
        let $table = $(this);
        if ($table.find('thead > tr > th.is-active').length) {
          return;
        }
        let cellCount = $table.find('thead > tr > th').length;
        let currentGrade = null;
        $table.find('tr').each(function () {
          let $row = $(this);
          let $thisGrade = $row.find('td.views-field-field-grade').first();
          if ($thisGrade.length && $thisGrade.text().trim()) {
            let thisGradeString =  $thisGrade.text().trim();
            if (currentGrade !== thisGradeString) {
              $('<tr><td colspan="' + cellCount + '"><em>' + thisGradeString + '</em></td></tr>').insertBefore($row);
              currentGrade = thisGradeString;
            }
          }

        });


      })

    }
  };

})(jQuery, Drupal);
