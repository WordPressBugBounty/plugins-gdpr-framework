jQuery(function($) {
  /**
   * Enable/disable every successful form control inside the hidden consent
   * repeater template -- not just <input>. Fix (SECURITY-AUDIT.md Finding 2 /
   * PR review Finding 2): the previous code only toggled `input`, leaving the
   * template's <textarea name="description"> enabled. The browser then
   * submitted an otherwise-empty consent row (description only), which backend
   * validation rejected as a missing slug/title and which could block
   * unrelated settings saves. `:input` also covers <textarea>, <select> and
   * <button>.
   */
  function setConsentRepeaterEnabled(enabled) {
    $(".js-gdpr-repeater .gdpr-show-hide")
      .find(":input")
      .prop("disabled", !enabled);
  }

  /**
   * requried issue on Consent show repeater
   */
  $(document).on("click", ".show_form_consent_gdpr", function(e) {
    $(".gdpr-show-hide").removeClass("gdpr-hidden");
    setConsentRepeaterEnabled(true);
    $(".show_form_consent_gdpr").hide();
  });
  /**
   * requried issue on Consent hide repeater
   */

  $(document).on("click", ".hide_form_consent_gdpr", function(e) {
    $(".gdpr-show-hide").addClass("gdpr-hidden");
    setConsentRepeaterEnabled(false);
    $(".show_form_consent_gdpr").show();
  });
  /**
   * Fix issue with more then one consent add.
   */
  $(document).ready(function() {
    setConsentRepeaterEnabled(
      !$(".js-gdpr-repeater .gdpr-show-hide").hasClass("gdpr-hidden")
    );
  });
  // Handler to open the modal dialog
  $(document).on("click", ".gdpr-open-modal", function(e) {
    $($(this).data("gdpr-modal-target")).dialog("open");
    e.preventDefault();
  });

  // Initialize all modals on page
  $(".gdpr-modal").each(function(i, e) {
    var $base = $(this);

    $base.dialog({
      title: $base.data("gdpr-title"),
      dialogClass: "wp-dialog",
      autoOpen: false,
      draggable: false,
      width: "auto",
      modal: true,
      resizable: false,
      closeOnEscape: true,
      position: {
        my: "center",
        at: "center",
        of: window
      },
      create: function() {
        // style fix for WordPress admin
        $(".ui-dialog-titlebar-close").addClass("ui-button");
      },
      open: function() {
        // Bind a click on the overlay to close the dialog
        $(".ui-widget-overlay").bind("click", function() {
          $base.dialog("close");
        });

        // Bind a custom close button to close the dialog
        $base.find(".gdpr-close-modal").bind("click", function(e) {
          $base.dialog("close");
          e.preventDefault();
        });

        // Fix overlay CSS issues in admin
        $(".wp-dialog").css("z-index", 9999);
        $(".ui-widget-overlay").css("z-index", 9998);
      },
      close: function() {
        $(".wp-dialog").css("z-index", 101);
        $(".ui-widget-overlay").css("z-index", 100);
      }
    });
  });

  /**
   * https://github.com/DubFriend/jquery.repeater
   */
  $(".js-gdpr-repeater").each(function() {
    var $repeater = $(this).repeater({
      isFirstItemUndeletable: true
    });
    if (window.repeaterData != undefined) {
      // will only work if repeater data is defined.
      if (typeof window.repeaterData[$(this).data("name")] !== undefined) {
        $repeater.setList(window.repeaterData[$(this).data("name")]);
      }
    }
  });

  /**
   * Init select2
   */
  $(".js-gdpr-select2").select2({
    width: "style"
  });

  /**
   * Auto-fill DPA info
   */
  $(".js-gdpr-country-selector").on("change", function() {
    var dpaData, $website, $email, $phone;
    var countryCode = $(this).val();

    if (!window.gdprDpaData[countryCode]) {
      return;
    }

    dpaData = window.gdprDpaData[countryCode];

    $website = $("#gdpr_dpa_website");
    if ("" === $website.data("set")) {
      $website.val(dpaData["website"]);
    }

    $email = $("#gdpr_dpa_email");
    if ("" === $email.data("set")) {
      $email.val(dpaData["email"]);
    }

    $phone = $("#gdpr_dpa_phone");
    if ("" === $phone.data("set")) {
      $phone.val(dpaData["phone"]);
    }
  });
});
