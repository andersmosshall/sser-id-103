(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.listTemplateBlock = {
    attach: function (context, settings) {
      if (drupalSettings && drupalSettings.listTemplateBlock && drupalSettings.listTemplateBlock.crossOverUids && drupalSettings.listTemplateBlock.crossOverUids.length) {
        drupalSettings.listTemplateBlock.crossOverUids.forEach((uid) => {
          if (uid !== '0') {
            $(once('list-row-uid--' + uid + '--processed', '.list-template-block .list-row-uid--' + uid, context))
              .each(function () {
                $(this).closest('tr').find('td.views-field-name').addClass('text-decoration--line-through');
            });
          }
        });
      }

      if (drupalSettings && drupalSettings.listTemplateBlock && drupalSettings.listTemplateBlock.customFields && drupalSettings.listTemplateBlock.customFields.length) {
        $(once('list-template-block--tr--processed', '.list-template-block tr', context))
          .each(function () {
            let $fields = $(this).find('td > .custom-field-placeholder');
            if ($fields.length) {
              drupalSettings.listTemplateBlock.customFields.forEach((customFieldSettings, key) => {
                if (customFieldSettings.size && $fields[key]) {
                  let heightToSet = null;
                  if (customFieldSettings.size === 'l') {
                    heightToSet = '150px';
                  }
                  if (customFieldSettings.size === 'xl') {
                    heightToSet = '300px';
                  }

                  if (heightToSet) {
                    let $field = $($fields[key]);
                    $field.css('height', heightToSet);
                    $field.css('min-height', heightToSet);
                  }
                }
              });
            }
          });
      }
      else {
        $('.list-template-block table.views-view-table', context).addClass('no-custom');
      }

      $(once('checkbox-placeholder--processed', '.list-template-block .checkbox-placeholder', context))
        .each(function () {
          $(this).html('<input type="checkbox" class="form-checkbox form-boolean form-boolean--type-checkbox">');
        });
    }
  };

})(jQuery, Drupal, drupalSettings);
