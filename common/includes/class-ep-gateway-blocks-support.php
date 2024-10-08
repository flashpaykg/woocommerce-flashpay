<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

class Ep_Gateway_Blocks_Support extends AbstractPaymentMethodType
{

	protected $payment_method;

	public function __construct($payment_method, $gateway)
	{
		$gateway_class = get_class($gateway);
		$icon = null;
		if (defined($gateway_class . '::ICON_NAME')) {
			$icon = ep_img_url(constant($gateway_class . '::ICON_NAME')) . '.svg';
		} elseif (defined($gateway_class . '::PAYMENT_METHOD')) {
			$icon = ep_img_url(constant($gateway_class . '::PAYMENT_METHOD')) . '.svg';
		}

		$this->payment_method = $payment_method;
		$this->name = sprintf('flashpay-%s', $this->payment_method);
		$this->data = [
			'title' => $gateway->settings['title'],
			'icon' => $icon,
			'description' => $gateway->settings['show_description'] === 'yes' ? $gateway->settings['description'] : '',
			'checkout_button_text' => $gateway->settings['checkout_button_text'],
			'enabled' => $gateway->settings['enabled'],
			'supports' => $gateway->supports,
		];

		if (isset ($gateway->settings['pp_mode'])) {
			$this->data['pp_mode'] = $gateway->settings['pp_mode'];
		}

		if (isset ($gateway->settings['pp_close_on_miss_click'])) {
			$this->data['pp_close_on_miss_click'] = $gateway->settings['pp_close_on_miss_click'];
		}
	}

	public function initialize()
	{
	}

	public function is_active()
	{
		return $this->data['enabled'] === 'yes';
	}

	public function get_payment_method_data()
	{
		return $this->data;
	}
}