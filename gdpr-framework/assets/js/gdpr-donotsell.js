(function ($) {

  var el_form = $('#form-new-post'),
      el_form_submit = $('.submit', el_form);

  // Fires when the form is submitted.
  el_form.on('submit', function (e) {
      e.preventDefault();

      el_form_submit.attr('disabled', 'disabled');
      //var donotsell_email= jQuery('input[name="donotsell_email"]').val();
      //console.log(isEmail(donotsell_email));
      new_post();
  });
  /* function isEmail(email) {
    var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    return regex.test(email);
  } */
  // Ajax request.
  function new_post() {
      $.ajax({
          url: localized_donot_sell_form.admin_donot_sell_ajax_url,
          type: 'POST',
          dataType: 'json',
          data: {
              action: 'donot_sell_save_post', // Set action without prefix 'wp_ajax_'.
              form_data: el_form.serialize()
          },
          cache: false
      }).done(function (r) {
          // Show a single, clearly styled status message. r.error is set both
          // for real errors and for the "verification required" response
          // (donot_sell_save_post()); anything else with a donotsellrequests
          // payload is a success.
          if (r && r.error !== undefined && r.error !== '') {
              showMessage(r.error, false);
          } else if (r && r.donotsellrequests) {
              showMessage('Request has been submitted successfully!!', true);
          } else {
              showMessage('Something went wrong. Please try again.', false);
          }
      }).fail(function () {
          showMessage('Something went wrong. Please try again.', false);
      }).always(function () {
          el_form_submit.removeAttr('disabled');
      });
  }

  // Render a success/error message in the status area and make sure it is
  // visible (the box is hidden again after a delay, and re-shown on the next
  // submit).
  function showMessage(text, isSuccess) {
      var $msg = jQuery('#donotsellmsg');

      $msg.stop(true, true)
          .toggleClass('donotsell-msg', isSuccess)
          .toggleClass('donotsell-error-msg', !isSuccess)
          .text(text)
          .show();

      if ($msg.length && $msg[0].scrollIntoView) {
          $msg[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
      }

      $msg.delay(10000).fadeOut();
  }

  // Used to trigger/simulate post submission without user action.
  function trigger_new_post() {
      el_form.trigger('submit');
  }

  // Sets interval so the post the can be updated automatically provided that it was already created.
  /* setInterval(function () {
      if (el_form_submit.attr('data-is-updated') === 'false') {
          return false;
      }

      trigger_new_post();
  }, 5000); // Set to 5 seconds. */

})(jQuery);