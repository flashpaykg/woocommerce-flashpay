<?php

// Declare Support For Cart+Checkout Blocks
add_action('before_woocommerce_init', function () {
	if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'cart_checkout_blocks',
			EP_PLUGIN_PATH,
			true
		);
	}
});

// Blocks Support
add_action('woocommerce_blocks_loaded', function () {

	if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
				$gateways = [
					Ep_Gateway_Settings_Applepay::ID => new Ep_Gateway_Applepay(),
					Ep_Gateway_Settings_Banks::ID => new Ep_Gateway_Banks(),
					Ep_Gateway_Settings_Blik::ID => new Ep_Gateway_Blik(),
					Ep_Gateway_Settings_Brazil_Online_Banks::ID => new Ep_Gateway_Brazil_Online_Banks(),
					Ep_Gateway_Settings_Card::ID => new Ep_Gateway_Card(),
					Ep_Gateway_Settings_Giropay::ID => new Ep_Gateway_Giropay(),
					Ep_Gateway_Settings_Googlepay::ID => new Ep_Gateway_Googlepay(),
					Ep_Gateway_Settings_Ideal::ID => new Ep_Gateway_Ideal(),
					Ep_Gateway_Settings_Klarna::ID => new Ep_Gateway_Klarna(),
					Ep_Gateway_Settings_More::ID => new Ep_Gateway_More(),
					Ep_Gateway_Settings_PayPal::ID => new Ep_Gateway_PayPal(),
					Ep_Gateway_Settings_PayPal_PayLater::ID => new Ep_Gateway_PayPal_PayLater(),
					Ep_Gateway_Settings_Sofort::ID => new Ep_Gateway_Sofort(),
				];

				foreach ($gateways as $id => $gateway) {
					$name = str_replace('flashpay-', '', $id);
					$payment_method_registry->register(new Ep_Gateway_Blocks_Support($name, $gateway));
				}
			}
		);
	}

});