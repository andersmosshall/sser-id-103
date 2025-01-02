(function ($, Drupal) {

  function getRowStateItem($element) {
    const namePrefixToSkip = [
      'update_reason',
    ];

    let value = $element.val();

    if ($element.is('input[type="radio"]')) {
      if (!$element.prop('checked')) {
        return null;
      }
    }

    if ($element.is('input[type="checkbox"]')) {
      value = $element.prop('checked');
    }

    let name = $element.attr('name');

    if (!name || !value) {
      return null;
    }

    let useValue = true;
    namePrefixToSkip.forEach(function (prefix) {
      if (name.startsWith(prefix)) {
        useValue = false;
        return null;
      }
    });

    if (!useValue) {
      return null;
    }

    return {
      name: name,
      value: value,
    }
  }

  function calculateRowState($row) {
    let rowState = {};

    let $inputs = $row.find('input');
    let $selects = $row.find('select');

    $inputs.each(function () {
      const rowStateItem = getRowStateItem($(this));
      if (rowStateItem) {
        rowState[rowStateItem.name] = rowStateItem.value;
      }
    });

    $selects.each(function () {
      const rowStateItem = getRowStateItem($(this));
      if (rowStateItem) {
        rowState[rowStateItem.name] = rowStateItem.value;
      }
    });

    // Sort object by key.
    const orderedRowState = {};

    Object.keys(rowState).sort().forEach(function(key) {
      orderedRowState[key] = rowState[key];
    });

    return JSON.stringify(orderedRowState);
  }

  function resolveUpdateReasonVisibility($row, $updateReasonWrapper) {
    const initialState = JSON.stringify($updateReasonWrapper.data('initial-state'));
    const currentState = calculateRowState($row);

    if (currentState !== initialState) {
      $updateReasonWrapper.show();
    }
    else {
      $updateReasonWrapper.hide();
    }
  }

  'use strict';
  Drupal.behaviors.ssrGradeRegistration = {
    attach: function (context, settings) {
      $(once('student-row--update-reason-wrapper--processed', '.student-row--update-reason-wrapper')).each(function () {
        let $updateReasonWrapper = $(this);
        let $row = $updateReasonWrapper.closest('.student-row--report-wrapper');

        let $inputs = $row.find('input');
        let $selects = $row.find('select');

        resolveUpdateReasonVisibility($row, $updateReasonWrapper);

        $inputs.each(function () {
          $(this).on('change', function () {
            resolveUpdateReasonVisibility($row, $updateReasonWrapper);
          });
        });
        $selects.each(function () {
          $(this).on('change', function () {
            resolveUpdateReasonVisibility($row, $updateReasonWrapper);
          });
        });
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
