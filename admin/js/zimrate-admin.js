(function ($) {
  "use strict";

  $(document).ready(function () {
    $(
      "." +
        window.zimrate.name +
        " a.btn-rate, ." +
        window.zimrate.name +
        " a.btn-remind, ." +
        window.zimrate.name +
        " a.btn-cancel"
    ).click(function (e) {
      e.preventDefault();

      $.post({
        url: window.zimrate.ajax_url,
        data: {
          _ajax_nonce: $(this).data("nonce"),
          action: window.zimrate.name + "-" + $(this).data("action"),
        },
        async: false,
        success: function (response) {
          if (response.redirect) {
            window.open(response.redirect, "_blank").focus();
          }
          $("." + window.zimrate.name + " .notice-dismiss").click();
        },
      });
    });
  });
})(jQuery);
