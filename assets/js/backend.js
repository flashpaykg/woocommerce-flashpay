;(function ($) {
  "use strict"

  EP.prototype.init = function () {
    this.infoBox.on("click", "[data-action]", $.proxy(this.callAction, this))
  }

  EP.prototype.callAction = function (e) {
    e.preventDefault()
    let target = $(e.target)
    let action = target.attr("data-action")

    if (typeof this[action] !== "undefined") {
      let message =
        target.attr("data-confirm") || "Are you sure you want to continue?"
      if (confirm(message)) {
        this[action]()
      }
    }
  }

  EP.prototype.refresh = function () {
    this.request({
      flashpay_action: "refresh",
    })
  }

  EP.prototype.refund = function () {
    this.request({
      flashpay_action: "refund",
    })
  }

  EP.prototype.request = function (dataObject) {
    let that = this
    return $.ajax({
      type: "POST",
      url: ajaxurl,
      dataType: "json",
      data: $.extend(
        {},
        {
          action: "flashpay_manual_transaction_actions",
          post: this.postID.val(),
        },
        dataObject
      ),
      beforeSend: $.proxy(this.showLoader, this, true),
      success: function () {
        $.get(window.location.href, function (data) {
          let newData = $(data)
            .find("#" + that.actionBox.attr("id") + " .inside")
            .html()
          that.actionBox.find(".inside").html(newData)
          newData = $(data)
            .find("#" + that.infoBox.attr("id") + " .inside")
            .html()
          that.infoBox.find(".inside").html(newData)
          that.showLoader(false)
        })
      },
      error: function (jqXHR) {
        alert(jqXHR.responseText)
        that.showLoader(false)
      },
    })
  }

  EP.prototype.showLoader = function (e, show) {
    if (show) {
      this.actionBox.append(this.loaderBox)
      this.infoBox.append(this.loaderBox)
    } else {
      this.actionBox.find(this.loaderBox).remove()
      this.infoBox.find(this.loaderBox).remove()
    }
  }

  // DOM ready
  $(function () {
    new EP().init()

    function epInsertAjaxResponseMessage(response) {
      if (response.hasOwnProperty("status") && response.status === "success") {
        let message = $(
          '<div id="message" class="updated"><p>' +
            response.message +
            "</p></div>"
        )
        message.hide()
        message.insertBefore($("#wc-ep_wiki"), null)
        message.fadeIn("fast", function () {
          setTimeout(function () {
            message.fadeOut("fast", function () {
              message.remove()
            })
          }, 5000)
        })
      }
    }

    let emptyLogsButton = $("#wc-ep_logs_clear")
    emptyLogsButton.on("click", function (e) {
      e.preventDefault()
      emptyLogsButton.prop("disabled", true)
      $.getJSON(
        ajaxurl,
        { action: "flashpay_empty_logs" },
        function (response) {
          epInsertAjaxResponseMessage(response)
          emptyLogsButton.prop("disabled", false)
        }
      )
    })

    let flushCacheButton = $("#wc-ep_flush_cache")
    flushCacheButton.on("click", function (e) {
      e.preventDefault()
      flushCacheButton.prop("disabled", true)
      $.getJSON(
        ajaxurl,
        { action: "flashpay_flush_cache" },
        function (response) {
          epInsertAjaxResponseMessage(response)
          flushCacheButton.prop("disabled", false)
        }
      )
    })
  })

  function EP() {
    this.actionBox = $("#flashpay-payment-actions")
    this.infoBox = $("#flashpay-payment-info")
    this.postID = $("#post_ID")
    this.loaderBox = $(
      '<div class="blockUI blockOverlay" style="z-index: 1000; border: medium none; margin: 0; padding: 0; width: 100%; height: 100%; top: 0; left: 0; background: rgb(255, 255, 255) none repeat scroll 0 0; opacity: 0.6; cursor: wait; position: absolute;"></div>'
    )
  }
})(jQuery)
