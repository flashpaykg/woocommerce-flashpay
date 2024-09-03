jQuery(document).ready(function () {
  /**
   * Wrapper available payment methods on checkout page
   */
  var $paymentMethodsWrapper = document.querySelector("#payment")?.parentElement;

  if (!$paymentMethodsWrapper) {
    return
  }

  /**
   * Check the availability of Apple Pay to make a payment.
   */
  var methodsMutationObserver = new MutationObserver(setApplePayVisibilty)

  methodsMutationObserver.observe($paymentMethodsWrapper, {
    childList: true,
    subtree: true,
  })

  function setApplePayVisibilty() {
    var $applePayMethod = jQuery(
      ".wc_payment_method.payment_method_flashpay-apple-pay"
    )

    if ($applePayMethod.length && isApplePayAllowed()) {
      $applePayMethod.addClass("ep-visible")
    }
  }

  function isApplePayAllowed() {
    return (
      Object.prototype.hasOwnProperty.call(window, "ApplePaySession") &&
      ApplePaySession.canMakePayments()
    )
  }
})
