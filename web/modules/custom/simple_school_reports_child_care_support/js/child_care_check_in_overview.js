(function ($, Drupal, drupalSettings) {
  'use strict';

  function displayMessages(wrapper) {
    const nowTs = Math.floor(Date.now() / 1000);
    const messageContainers = wrapper.find('.child-care-check-in-message, .child-care-global-check-in-message');
    messageContainers.each(function () {
      const messageContainer = $(this);
      const displayThreshold = messageContainer.data('display-threshold');

      if (displayThreshold && nowTs >= displayThreshold) {
        messageContainer.show();
      }
      else {
        messageContainer.hide();
      }
    });

  }

  Drupal.behaviors.ChildCareCheckInOverview = {
    attach: function (context, settings) {
      $(once('child-care-day-overview-container--messages-processed', '.child-care-day-overview-container', context)).each(function () {
        const wrapper = $(this);
        displayMessages(wrapper);
        // Rerun every minute.
        setInterval(() => displayMessages(wrapper), 60 * 1000);

      });
    }
  };
})(jQuery, Drupal, drupalSettings);
