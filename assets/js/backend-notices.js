;(function ($, config) {
  $(function () {
    $(document.body).on("click", ".wc-ep-notice .notice-dismiss", function () {
      $.post(config.flush)
    })
  })
})(jQuery, window.wcEpBackendNotices || {})
