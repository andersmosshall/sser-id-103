(function ($, Drupal, drupalSettings) {
  function updateLabel($label, $subjectName, $teacherListObject) {
    let skip = true;

    let firstNames = [];
    let subjectNameSuffix = '';
    $teacherListObject.find('li').each(function () {
      firstNames.push($(this).text().split(' ')[0]);
    });
    if (firstNames.length) {
      subjectNameSuffix = ' - ' + firstNames.join(', ');
      skip = false;
    }
    $label.text($subjectName + subjectNameSuffix);

    if (skip) {
      let $defaultGradeRoundSelect = $teacherListObject.closest('.subject-grade-container').find('select[name*="field_default_grade_round"]');
      if ($defaultGradeRoundSelect.length && $defaultGradeRoundSelect.val()) {
        let round = $defaultGradeRoundSelect.find('option:selected').text() || $defaultGradeRoundSelect.val();
        $label.text($subjectName + ' - ' + Drupal.t('Grade from @grade_round', {'@grade_round': round }));
        skip = false;
      }
    }


    if (!skip) {
      $label.css('text-decoration', '');
      $label.css('color', '');
    }
    else {
      $label.css('text-decoration', 'line-through');
      $label.css('color', '#d72222');
    }
  }

  'use strict';
  Drupal.behaviors.gradeRoundStudentGroup = {
    attach: function (context, settings) {
      $(once('subject-grade-container--processed', '.subject-grade-container')).each(function () {
        let $this = $(this);

        let $label = $this.find('summary');
        let subjectName = $this.find('.label.hidden').text();
        let $teacherListObject = $this.find('.improvedselect_sel');

        $this.find('.improvedselect_sel').each(function () {
          updateLabel($label, subjectName, $teacherListObject);
        });

        $this.find('.improvedselect_sel').on('DOMSubtreeModified', function () {
          updateLabel($label, subjectName, $teacherListObject);
        });

        $this.find('select[name*="field_default_grade_round"]').change(function () {
          updateLabel($label, subjectName, $teacherListObject);
        });
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
