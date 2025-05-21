(function ($, Drupal, once) {
  'use strict';

  function handleHideGroups($form, $deviationCheckbox, $numberOfGroupsSelect) {
    if ($deviationCheckbox.is(':checked')) {
      $numberOfGroupsSelect.closest('.field--name-relevant-groups').show();
    }
    else {
      $numberOfGroupsSelect.closest('.field--name-relevant-groups').hide();
    }

    const hideFrom = parseInt(!$deviationCheckbox.is(':checked') ? '0' : $numberOfGroupsSelect.val().toString(), 10) + 1;
    const hideTo = $numberOfGroupsSelect.val().toString() === '5' ? 6 : 5;


    for (let i = 1; i < hideFrom; i++) {
      $form.find(`.ssr-schema-entry-sub-group-${i}`).show();
    }

    for (let i = hideFrom; i <= hideTo; i++) {
      $form.find(`.ssr-schema-entry-sub-group-${i}`).hide();
    }
  }

  function handleHidePeriodicityElements($periodicitySelect, $specifiedPeriodicitySelect, $periodicityStart, i) {
    const showSpecifiedPeriodicitySelect = $periodicitySelect.val() === 'custom';
    const showPeriodicityStart = $periodicitySelect.val() === 'custom';

    if (showSpecifiedPeriodicitySelect) {
      $specifiedPeriodicitySelect.closest(`.field--name-custom-periodicity-${i}`).show();
    }
    else {
      $specifiedPeriodicitySelect.closest(`.field--name-custom-periodicity-${i}`).hide();
    }

    if (showPeriodicityStart) {
      $periodicityStart.closest(`.field--name-custom-periodicity-start-${i}`).show();
    }
    else {
      $periodicityStart.closest(`.field--name-custom-periodicity-start-${i}`).hide();
    }

  }

  Drupal.behaviors.ssrSchemaEntryForm = {
    attach: function (context, settings) {
      $(once('ief-ssr-schema-entry--processed', '.field--name-field-ssr-schema .ief-form')).each(function () {
        const $form = $(this);

        // Handle group visibility.
        const $deviationCheckbox = $form.find('.field--name-deviated input[type="checkbox"]');
        const $numberOfGroupsSelect = $form.find('.field--name-relevant-groups select');
        handleHideGroups($form, $deviationCheckbox, $numberOfGroupsSelect);
        $deviationCheckbox.change(function () {
          handleHideGroups($form, $deviationCheckbox, $numberOfGroupsSelect);
        });
        $numberOfGroupsSelect.change(function () {
          handleHideGroups($form, $deviationCheckbox, $numberOfGroupsSelect);
        });

        // Handle field visibility.
        for (let i = 1; i <= 5; i++) {
          const $periodicitySelect = $form.find(`.field--name-periodicity-${i} select`);
          const $specifiedPeriodicitySelect = $form.find(`.field--name-custom-periodicity-${i} select`);
          const $periodicityStart = $form.find(`.field--name-custom-periodicity-start-${i} input[type="date"]`);
          handleHidePeriodicityElements($periodicitySelect, $specifiedPeriodicitySelect, $periodicityStart, i);
          $periodicitySelect.change(function () {
            handleHidePeriodicityElements($periodicitySelect, $specifiedPeriodicitySelect, $periodicityStart, i);
          });
        }
      })
    }
  };

})(jQuery, Drupal, once);
