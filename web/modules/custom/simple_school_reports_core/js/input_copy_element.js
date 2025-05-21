(function ($, Drupal) {

  function onTargetFocus($target, $controller, $controllerLabel) {
    let id = $target.attr('id');
    $controller.data('active-id', id);
    $controller.css('display','');
    let fallbackLabel = Drupal.t('Copy item');
    let label = $target.data('copy-to-all-label');
    if (!label) {
      label = $target.closest('.form-item').find('label').text();
      if (label) {
        label = Drupal.t('Copy @label', {'@label': label});
      }
    }
    $controllerLabel.text(label || fallbackLabel);
  }

  function onTargetFocusOut($target, $controller, $controllerLabel) {
    let id = $target.attr('id');

    setTimeout(() => {
      if ($controller.data('active-id') === id) {
        $controllerLabel.text('');
        $controller.css('display','none');
      }
    },500);
  }

  function setupEditorListener($textarea, $controller, $controllerLabel, iteration = 0) {
    if (iteration > 100) {
      return;
    }

    let editor = getEditor($textarea);
    if (!editor) {
      setTimeout(() => {
        setupEditorListener($textarea, $controller, $controllerLabel, iteration++);
      }, 500);
      return;
    }

    editor.ui.focusTracker.on( 'change:isFocused', ( evt, data, isFocused ) => {
      if (isFocused) {
        onTargetFocus($textarea, $controller, $controllerLabel);
      }
      else {
        onTargetFocusOut($textarea, $controller, $controllerLabel);
      }
    } );
  }

  function getEditor($textarea) {
    let ckEditorId = $textarea.data('ckeditor5-id');

    if (!ckEditorId) {
      return null;
    }

    let editor = Drupal.CKEditor5Instances.get(ckEditorId.toString());
    if (!editor) {
      return null;
    }
    return editor;
  }

  'use strict';
  Drupal.behaviors.inputCopyElement = {
    attach: function (context, settings) {
      $(once('input-copy-element--processed', '.input-copy-element')).each(function () {

        let $controller = $(this);
        let $controllerLabel = $controller.find('.input-copy-element--label');
        let $targetSelectors = $controller.data('target-selectors');

        if (Array.isArray($targetSelectors)) {
          $targetSelectors = $targetSelectors.join(', ');
        }

        $($targetSelectors).each(function () {
          let $target = $(this);

          let targetType = $target.prop('nodeName').toLowerCase();
          switch (targetType) {

            case 'textarea':
              setupEditorListener($target, $controller, $controllerLabel);
              break;

            default:
              $target.focus(function () {
                onTargetFocus($target, $controller, $controllerLabel);
              });

              $target.focusout(function () {
                onTargetFocusOut($target, $controller, $controllerLabel);
              });
          }

        });

        $controller.find('.input-copy-element--trigger .button').click(function () {
          let sourceId = $controller.data('active-id');

          let $source = $('#' + sourceId);

          let value = null;
          let sourceType = null;

          if ($source.length) {
            sourceType = $source.prop('nodeName').toLowerCase();
            switch (sourceType) {
              case 'input':
                value = $source.val();
                break;

              case 'select':
                value = $source.find(":selected").val();
                break;

              case 'textarea':
                let editor = getEditor($source);
                if (!editor) {
                  break;
                }

                value = editor.getData() || '';
                break;

              default:
                // Do nothing.
            }

          }

          if (value !== null && value !== undefined) {
            $($targetSelectors).each(function () {

              let $target = $(this);
              if ($target.attr('id') === sourceId) {
                return;
              }

              let targetType = $target.prop('nodeName').toLowerCase();
              if (targetType !== sourceType) {
                return;
              }

              switch (targetType) {
                case 'input':
                  $target.val(value).change();
                  break;

                case 'select':
                  $target.val(value).change();
                  break;

                case 'textarea':
                  let editor = getEditor($target);
                  if (!editor) {
                    break;
                  }

                  editor.setData(value);
                  break;

                default:
                // Do nothing.
              }


            });
          }
        });
      });
    }
  };

})(jQuery, Drupal);
