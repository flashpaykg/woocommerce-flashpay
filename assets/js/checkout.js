jQuery(document).ready(function () {
  jQuery(document.body).append(
    '<div id="flashpay-overlay-loader" class="blockUI blockOverlay flashpay-loader-overlay" style="display: none;"></div>'
  )
  window.EP.isEmbeddedMode = false
  var targetForm
  var loader = jQuery("#flashpay-loader")
  window.EP.isPaymentRunning = false
  window.EP.paramsForEmbeddedPP = false
  window.EP.clarificationRunning = false
  window.EP.lastEmbeddedRequestTime = 0
  window.EP.redirectResult = false

  // Create order via AJAX in case of "popup" or "iframe" mode
  jQuery("body").on("click", "#place_order", function (e) {
    if (!isFlashpayPayment()) {
      return
    }
    targetForm = jQuery(e.target.form)
    e.preventDefault()
    if (window.EP.isEmbeddedMode && isFlashpayCardPayment()) {
      startEmbeddedIframeFlow()
      return
    }

    var href = window.location.href.split("?")
    var data = targetForm.serializeArray()
    var query_string = href[1] === undefined ? "" : href[1]

    data.push({
      name: "action",
      value: "flashpay_process",
    })

    if (EP.order_id > 0) {
      data.push({
        name: "order_id",
        value: EP.order_id,
      })
    }

    jQuery.ajax({
      type: "POST",
      url: EP.ajax_url + "?" + query_string,
      data: data,
      dataType: "json",
      success: success,
      error: function (jqXHR, textStatus, errorThrown) {
        submit_error('<div class="woocommerce-error">' + errorThrown + "</div>")
      },
    })
  })

  // update embedded iframe when updating cart (taxes, delivery, etc)
  jQuery(document.body).on("updated_checkout", function () {
    resetEmbeddedIframe()
  })

  getParamsForCreateEmbeddedPP()

  function resetEmbeddedIframe() {
    window.EP.paramsForEmbeddedPP = false
    jQuery("#flashpay-iframe-embedded").height(0).empty()
    jQuery("#flashpay-loader-embedded").show()
    getParamsForCreateEmbeddedPP()
  }

  function loadEmbeddedIframe() {
    var embeddedIframeDiv = jQuery("#flashpay-iframe-embedded")
    if (embeddedIframeDiv.length === 1 && window.EP.paramsForEmbeddedPP) {
      jQuery("#flashpay-iframe-embedded").empty()
      window.EP.isEmbeddedMode = true
      loader = jQuery("#flashpay-loader-embedded")
      showIFrame(window.EP.paramsForEmbeddedPP)
      jQuery('input[name="payment_method"]').change(function () {
        if (isFlashpayCardPayment()) {
          jQuery(window).trigger("resize")
        }
      })
    }
  }

  function isFlashpayPayment() {
    return (
      jQuery("input[name='payment_method']:checked").val().slice(0, 8) ===
      "flashpay"
    )
  }

  function isFlashpayCardPayment() {
    return (
      jQuery("input[name='payment_method']:checked").val() === "flashpay-card"
    )
  }

  function submit_error(error_message) {
    jQuery(
      ".woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message"
    ).remove()
    targetForm.prepend(
      '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' +
        error_message +
        "</div>"
    )
    targetForm.removeClass("processing").unblock()
    targetForm
      .find(".input-text, select, input:checkbox")
      .trigger("validate")
      .blur()
    scroll_to_notices()
    jQuery(document.body).trigger("checkout_error")
  }

  function clear_error() {
    jQuery(
      ".woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message"
    ).remove()
  }

  function scroll_to_notices() {
    var scrollElement = jQuery(
        ".woocommerce-NoticeGroup-updateOrderReview, .woocommerce-NoticeGroup-checkout"
      ),
      isSmoothScrollSupported =
        "scrollBehavior" in document.documentElement.style

    if (!scrollElement.length) {
      scrollElement = loader
    }

    if (scrollElement.length) {
      if (isSmoothScrollSupported) {
        scrollElement[0].scrollIntoView({
          behavior: "smooth",
        })
      } else {
        jQuery("html, body").animate(
          {
            scrollTop: scrollElement.offset().top - 100,
          },
          1000
        )
      }
    }
  }

  function show_error(result, message) {
    console.error(message)

    if (true === result.reload) {
      window.location.reload()
      return
    }

    if (true === result.refresh) {
      jQuery(document.body).trigger("update_checkout")
    }

    if (result.messages) {
      submit_error(result.messages)
    } else {
      submit_error(
        '<div class="woocommerce-error">' +
          wc_checkout_params.i18n_checkout_error +
          "</div>"
      )
    }
  }

  function success(result) {
    switch (result.result) {
      case "success":
        EP.order_id = result.order_id
        if (window.EP.isEmbeddedMode && isFlashpayCardPayment()) {
          processOrderWithEmbeddedIframe(result)
          break
        }
        switch (window.EP.paramsForEmbeddedPP.frame_mode) {
          case "popup":
            const options = JSON.parse(result.optionsJson)
            showPopup(options)
            break
          default:
            redirect(result.redirect)
            break
        }
        break
      case "failure":
        show_error(result, "Result failure")
        break
      default:
        show_error(result, "Invalid response")
    }
  }

  function runWidget(configObj) {
    configObj.onExit = back
    configObj.onDestroy = back
    EPayWidget.run(configObj, "POST")
  }

  function showPopup(configObj) {
    runWidget(configObj)
  }

  function showIFrame(configObj) {
    configObj.onLoaded = onLoaded
    configObj.onEmbeddedModeCheckValidationResponse =
      onEmbeddedModeCheckValidationResponse
    configObj.onEnterKeyPressed = onEnterKeyPressed
    configObj.onPaymentSent = showOverlayLoader
    configObj.onSubmitClarificationForm = showOverlayLoader
    configObj.onShowClarificationPage = onShowClarificationPage
    configObj.onEmbeddedModeRedirect3dsParentPage =
      onEmbeddedModeRedirect3dsParentPage
    configObj.onPaymentSuccess = redirectOnSuccess
    configObj.onCardVerifySuccess = redirectOnSuccess
    configObj.onPaymentFail = redirectOnFail
    configObj.onCardVerifyFail = redirectOnFail

    loader.show()
    scroll_to_notices()
    runWidget(configObj)
  }

  function onLoaded() {
    loader.hide()
    jQuery("#flashpay-iframe-embedded").height("auto")
  }

  function back() {
    var href = window.location.href.split("?")
    var query_string = href[1] === undefined ? "" : href[1]
    var data = []

    data.push({
      name: "action",
      value: "flashpay_break",
    })

    if (EP.order_id > 0) {
      data.push({
        name: "order_id",
        value: EP.order_id,
      })
    }

    jQuery.ajax({
      type: "POST",
      url: EP.ajax_url + "?" + query_string,
      data: data,
      dataType: "json",
      success: function (result) {
        window.location.replace(result.redirect)
      },
      error: function (jqXHR, textStatus, errorThrown) {
        submit_error('<div class="woocommerce-error">' + errorThrown + "</div>")
      },
    })
  }

  function redirect(url) {
    window.location.href = url
  }

  /* Embedded iFrame flow */

  // Step1 . On page load - init payment form with minimum params
  function getParamsForCreateEmbeddedPP() {
    var href = window.location.href.split("?")
    var data = [
      {
        name: "action",
        value: "get_data_for_payment_form",
      },
    ]
    var query_string = href[1] === undefined ? "" : href[1]

    if (EP.order_id > 0) {
      data.push({
        name: "order_id",
        value: EP.order_id,
      })
    }

    const requestTime = Date.now()
    window.EP.lastEmbeddedRequestTime = requestTime

    jQuery.ajax({
      type: "POST",
      url: EP.ajax_url + "?" + query_string,
      data: data,
      dataType: "json",
      success: function (result) {
        if (requestTime === window.EP.lastEmbeddedRequestTime) {
          window.EP.paramsForEmbeddedPP = result
          loadEmbeddedIframe()
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        submit_error('<div class="woocommerce-error">' + errorThrown + "</div>")
      },
    })
  }

  // Step 2. Button "Place order" - onclick, send message to iframe, call form validation
  function startEmbeddedIframeFlow() {
    window.EP.isPaymentRunning = true

    if (EP.order_id) {
      window.postMessage(
        '{"message":"epframe.embedded_mode.check_validation","from_another_domain":true}'
      )
      return
    }

    const data = [
      { name: "action", value: "check_cart_amount" },
      { name: "amount", value: window.EP.paramsForEmbeddedPP.payment_amount },
    ]
    jQuery.ajax({
      type: "POST",
      url: EP.ajax_url,
      data: data,
      dataType: "json",
      success: function (result) {
        if (result.amount_is_equal) {
          window.postMessage(
            '{"message":"epframe.embedded_mode.check_validation","from_another_domain":true}'
          )
        } else {
          window.location.reload()
        }
      },
      error: (jqXHR, textStatus, errorThrown) => {
        alert(textStatus)
      },
    })
  }

  // Step3. Listen Answer from Iframe about form validation
  function onEmbeddedModeCheckValidationResponse(data) {
    if (!window.EP.isPaymentRunning) {
      return
    }
    if (!!data && Object.keys(data).length > 0) {
      var errors = []
      var errorText = ""
      jQuery.each(data, function (key, value) {
        errors.push(value)
      })
      var errorsUnique = [...new Set(errors)] //remove duplicated
      jQuery.each(errorsUnique, function (key, value) {
        errorText += value + "<br>"
      })
      submit_error('<div class="woocommerce-error">' + errorText + "</div>")
      window.EP.isPaymentRunning = false
    } else {
      clear_error()
      if (window.EP.clarificationRunning) {
        postSubmit({})
      } else {
        createWoocommerceOrder()
      }
    }
  }

  // Step 4. Create Wocommerce Order
  function createWoocommerceOrder() {
    var href = window.location.href.split("?")
    var data = targetForm.serializeArray()
    var query_string = href[1] === undefined ? "" : href[1]
    data.push({
      name: "action",
      value: "flashpay_process",
    })
    if (EP.order_id > 0) {
      data.push({
        name: "order_id",
        value: EP.order_id,
      })
    }
    data.push({
      name: "payment_id",
      value: window.EP.paramsForEmbeddedPP.payment_id,
    })
    jQuery.ajax({
      type: "POST",
      url: EP.ajax_url + "?" + query_string,
      data: data,
      dataType: "json",
      success: success,
      error: function (jqXHR, textStatus, errorThrown) {
        submit_error('<div class="woocommerce-error">' + errorThrown + "</div>")
      },
    })
  }

  // Step 5 send payment request via post message
  function processOrderWithEmbeddedIframe(result) {
    window.EP.redirectResult = JSON.parse(result.optionsJson)
    window.EP.redirectResult.frame_mode = "iframe"
    window.EP.redirectResult.payment_id =
      window.EP.paramsForEmbeddedPP.payment_id
    var billingFields = [
      "billing_address",
      "billing_city",
      "billing_country",
      "billing_postal",
      "customer_first_name",
      "customer_last_name",
      "customer_phone",
      "customer_zip",
      "customer_address",
      "customer_city",
      "customer_country",
      "customer_email",
    ]
    var fieldsObject = {}
    Object.keys(window.EP.redirectResult).forEach((key) => {
      var name = key
      if (billingFields.includes(key)) {
        name = "BillingInfo[" + name + "]"
      }
      fieldsObject[name] = window.EP.redirectResult[key]
      if (key === "billing_country") {
        fieldsObject["BillingInfo[country]"] = window.EP.redirectResult[key]
      }
    })

    postSubmit(fieldsObject)
  }

  function postSubmit(fields) {
    var message = { message: "epframe.embedded_mode.submit" }
    message.fields = fields
    message.from_another_domain = true
    window.postMessage(JSON.stringify(message))
  }

  function onEnterKeyPressed() {
    jQuery("#place_order").click()
  }

  function redirectOnSuccess() {
    if (window.EP.redirectResult.redirect_success_enabled) {
      hideOverlayLoader()
      window.location.replace(window.EP.redirectResult.redirect_success_url)
    }
  }

  function redirectOnFail() {
    if (window.EP.redirectResult.redirect_fail_enabled) {
      hideOverlayLoader()
      window.location.replace(window.EP.redirectResult.redirect_fail_url)
    }
  }

  function onEmbeddedModeRedirect3dsParentPage(data) {
    var form = document.createElement("form")
    form.setAttribute("method", data.method)
    form.setAttribute("action", data.url)
    form.setAttribute("style", "display:none;")
    form.setAttribute("name", "3dsForm")
    for (let k in data.body) {
      const input = document.createElement("input")
      input.name = k
      input.value = data.body[k]
      form.appendChild(input)
    }
    document.body.appendChild(form)
    form.submit()
  }

  function showOverlayLoader() {
    jQuery("#flashpay-overlay-loader").show()
  }

  function hideOverlayLoader() {
    jQuery("#flashpay-overlay-loader").hide()
  }

  function onShowClarificationPage() {
    window.EP.clarificationRunning = true
    hideOverlayLoader()
  }
})
