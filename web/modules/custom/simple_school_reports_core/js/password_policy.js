(function ($, Drupal, debounce) {

  function setupStatusAwaitMessage() {
    const $unprocessedStatusElement = $('.password-policy-constraint--status:not(.password-policy-constraint--status--pending)');
    if ($unprocessedStatusElement.length === 0) {
      return;
    }
    $unprocessedStatusElement.addClass('password-policy-constraint--status--pending');
    $unprocessedStatusElement.text(Drupal.t('Password strength will be verified when you proceed with the next user field.'));
  }

  function setupStatusPendingMessage() {
    const $unprocessedStatusElement = $('.password-policy-constraint--status, .password-policy-constraint--status--pending');
    if ($unprocessedStatusElement.length === 0) {
      return;
    }
    $unprocessedStatusElement.addClass('password-policy-constraint--status--pending');
    $unprocessedStatusElement.text(Drupal.t('Verifying password strength...'));
  }

  Drupal.behaviors.ssrPasswordPolicy = {
    attach(context, settings) {
      once('ssr-password-policy', 'input.js-password-field', context).forEach((value) => {
        const $mainInput = $(value);
        $mainInput.on('input', setupStatusAwaitMessage);
        $mainInput.on('blur', setupStatusPendingMessage)
      });
    },
  };
})(jQuery, Drupal, Drupal.debounce);

