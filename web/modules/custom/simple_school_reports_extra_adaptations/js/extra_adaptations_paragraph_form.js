(function ($, Drupal, drupalSettings) {
  'use strict';

  function handleExtraAdaptationChange($select, $subjectsDetailsWrapper) {
    const selectedValue = $select.val() || 'none';

    const extraAdaptationSubjectMap = drupalSettings.extraAdaptationSubjectMap || {};
    const allowedSubjects = extraAdaptationSubjectMap[selectedValue] || [];

    if (allowedSubjects.length === 0) {
      $subjectsDetailsWrapper.hide();
      return;
    }

    $subjectsDetailsWrapper.show();
    $subjectsDetailsWrapper.find('input[type="checkbox"]').each(function () {
      const $checkbox = $(this);
      const subjectId = $checkbox.val();

      const toHide = !allowedSubjects.includes(subjectId);
      if (toHide) {
        $checkbox.closest('.form-item').hide();
        return;
      }
      $checkbox.closest('.form-item').show();
    });
  }

  Drupal.behaviors.extraAdaptationsUserForm = {
    attach: function (context, settings) {
      $(once('extra-adaptation-select--processed', '[class*="subform-field-extra-adaptation"] select', context))
        .each(function () {
          const $select = $(this);
          const $subjectsDetailsWrapper = $select.closest('.paragraphs-subform').find('.extra-adaptation-school-subject-edit-wrapper');
          handleExtraAdaptationChange($select, $subjectsDetailsWrapper);
          $select.on('change', function () {
            handleExtraAdaptationChange($select, $subjectsDetailsWrapper);
          });
        });
    }
  };

})(jQuery, Drupal, drupalSettings);
