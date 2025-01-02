(function ($, Drupal, drupalSettings) {

  function resolveFillLinkDisplay($selectWrapper) {
    let $link = $selectWrapper.find('.button--wrapper a.button');
    if ($link.length) {
      let $select = $selectWrapper.find('.form-element--type-select');
      let hideLink = true;
      if ($select.length) {
        let value = $select.find(':selected').val();
        if (value) {
          hideLink = false;
        }
      }

      if (hideLink) {
        $link.css('display', 'none');
      }
      else {
        $link.css('display', '');
      }
    }
  }
  'use strict';
  Drupal.behaviors.standardIUPGoalSelect = {
    attach: function (context, settings) {
      $(once('standard-iup-goal-select--wrapper--processed', '.standard-iup-goal-select--wrapper', context)).each(function () {
        let $selectWrapper = $(this);
        let $link = $selectWrapper.find('.button--wrapper a.button');
        let $select = $selectWrapper.find('.form-element--type-select');

        if ($link.length && $select.length) {
          const $iefForm = $select.closest('.ief-form, .form');
          if ($iefForm.length) {
            const ckEditorId = $iefForm.find('.field--name-field-iup-goal textarea').data('ckeditor5-id');
            let $subjectField = $iefForm.find('.field--name-field-school-subject select');
            $link.click(function () {
              let value = $select.find(':selected').val();
              if (value) {
                let valueParts = value.split(':');
                let subjectId = '_none';
                if (valueParts.length > 1) {
                  subjectId = valueParts[1];
                }
                $subjectField.val(subjectId).change();

                let text = drupalSettings && drupalSettings.iupGoalMap && drupalSettings.iupGoalMap[value] ? drupalSettings.iupGoalMap[value] : null;

                if (text && ckEditorId && Drupal.CKEditor5Instances) {
                  let editor = Drupal.CKEditor5Instances.get(ckEditorId.toString());
                  if (!editor) {
                    return;
                  }

                  // Convert newline to <br> tag.
                  text = text.replace(/\n/g, '<br>');
                  let data = '<p>' + text +' </p>';
                  editor.setData(data);
                  editor.model.change(writer => {
                    writer.setSelection(editor.model.document.getRoot(), 'end');
                  });
                  editor.editing.view.focus();
                  setTimeout(() => {
                    $select[0].scrollIntoView({
                      behavior: 'auto',
                      block: 'center',
                      inline: 'center'
                    });
                  }, 0);
                }
              }
              $select.val('').change();
            });
          }

          resolveFillLinkDisplay($selectWrapper);
          $selectWrapper.change(function () {
            resolveFillLinkDisplay($selectWrapper);
          });
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
