(function ($, Drupal) {

  function resolveAddLinkDisplay($selectWrapper) {
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
  Drupal.behaviors.standardPhraseSelect = {
    attach: function (context, settings) {
      $(once('standard-phrase-select--wrapper--processed', '.standard-phrase-select--wrapper', context)).each(function () {
        let $selectWrapper = $(this);
        let $link = $selectWrapper.find('.button--wrapper a.button');
        let $select = $selectWrapper.find('.form-element--type-select');

        if ($link.length && $select.length) {
          let ckEditorElementId = $select.data('ck-editor-id');
          let textMap = $select.data('text-map');
          $link.click(function () {

            let value = $select.find(':selected').val();
            if (value) {

              let ckEditorId = $('#' + ckEditorElementId).data('ckeditor5-id');

              let text = textMap && textMap[value] ? textMap[value] : null;

              if (text && ckEditorId) {
                if (Drupal.CKEditor5Instances) {
                  let editor = Drupal.CKEditor5Instances.get(ckEditorId.toString());
                  if (!editor) {
                    return;
                  }

                  let data = editor.getData() || '';

                  text = text.replace(/\n/g, '<br>');
                  data += '<p>' + text +' </p>'
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
            }
            $select.val('').change();
          });

          resolveAddLinkDisplay($selectWrapper);
          $selectWrapper.change(function () {
            resolveAddLinkDisplay($selectWrapper);
          });
        }
      });
    }
  };

})(jQuery, Drupal);
