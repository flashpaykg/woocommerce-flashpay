<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
* <h2>FLASHPAY Gateway PayPal PayLater.</h2>
*
* @class    Ep_Gateway_PayPal_PayLater
* @version  3.4.3
* @package  Woocommerce_Flashpay/Classes
* @category Class
*/
class Ep_Gateway_PayPal_PayLater extends Ep_Gateway
{
    const PAYMENT_METHOD = 'paypal-wallet';
    const ICON_NAME = 'paypal-paylater';
    
    /**
    * @inheritDoc
    * @override
    * @var string[]
    * @since 3.4.3
    */
    public $supports = [
        'products',
        'refunds',
    ];

    /**
    * <h2>Instance of FLASHPAY PayPal Gateway.</h2>
    *
    * @var Ep_Gateway
    * @since 3.4.3
    */
    private static $_instance;
    
    /**
    * <h2>Returns a new instance of self, if it does not already exist.</h2>
    *
    * @return static
    * @since 3.4.3
    */
    public static function get_instance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }
    
    public function __construct()
    {
        $this->id = Ep_Gateway_Settings_PayPal_PayLater::ID;
        $this->method_title = __('FLASHPAY PayPal PayLater', 'woo-flashpay');
        $this->method_description = __('Accept payments via PayPal Buy Now Pay Later.', 'woo-flashpay');
        $this->has_fields = false;
        $this->title = $this->get_option(Ep_Gateway_Settings::OPTION_TITLE);
        $this->order_button_text = $this->get_option(Ep_Gateway_Settings::OPTION_CHECKOUT_BUTTON_TEXT);
        $this->enabled = $this->get_option(Ep_Gateway_Settings::OPTION_ENABLED);
        $this->icon = $this->get_icon();

        if ($this->is_enabled(Ep_Gateway_Settings::OPTION_SHOW_DESCRIPTION)) {
            $this->description = $this->get_option(Ep_Gateway_Settings::OPTION_DESCRIPTION);
        }

        parent::__construct();
    }
    
    /**
    * @inheritDoc
    * @override
    * @return array
    * @since 3.4.3
    */
    public function apply_payment_args(array $values, Ep_Gateway_Order $order): array
    {
        $values = apply_filters('ep_append_force_mode', $values, self::PAYMENT_METHOD);
        $values['payment_methods_options'] = '{"submethod_code": "paylater"}';
        return parent::apply_payment_args($values, $order);
    }
    
    /**
    * @return string
    * @since 3.4.3
    */
    public function get_refund_endpoint($order): string
    {
        return 'wallet/paypal';
    }

    /**
    * @inheritDoc
    * @override
    * @return array <p>Settings for redirecting to the FLASHPAY payment page.</p>
    * @since 3.4.3
    */
    public function process_payment($order_id)
    {
        $order = ep_get_order($order_id);
        $options = ep_payment_page()->get_request_url($order, $this);
        $payment_page_url = ep_payment_page()->get_url() . '/payment?' . http_build_query($options);

        return [
            'result' => 'success',
            'redirect' => $payment_page_url,
            'order_id' => $order_id,
        ];
    }
    
    /**
    * @inheritDoc
    * @override
    * @return bool <p><b>TRUE</b> on process completed successfully, <b>FALSE</b> otherwise.</p>
    * @throws Ep_Gateway_API_Exception
    * @throws Ep_Gateway_Logic_Exception
    * @throws WC_Data_Exception
    * @since 3.4.3
    */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        return Ep_Gateway_Module_Refund::get_instance()->process($order_id, $amount, $reason);
    }
    
    /**
    * @inheritDoc
    * <p>If false, the automatic refund button is hidden in the UI.</p>
    *
    * @param WC_Order $order <p>Order object.</p>
    * @override
    * @return bool <p><b>TRUE</b> if a refund available for the order, or <b>FALSE</b> otherwise.</p>
    * @since 3.4.3
    */
    public function can_refund_order($order): bool
    {
        if (!$order) {
            ep_get_log()->debug(
                _x('Undefined argument order. Hide refund via FLASHPAY button.', 'Log information', 'woo-flashpay')
            );
            return false;
        }

        $order = ep_get_order($order);

        // Check if there is a FLASHPAY payment
        if (!$order->is_ep()) {
            return false;
        }

        return Ep_Gateway_Module_Refund::get_instance()->is_available($order);
    }
    
    /**
    * @inheritDoc
    * @override
    * @return string DOM element img as a string
    * @since 3.4.3
    */
    public function get_icon()
    {
        $icon_str = sprintf(
            '<img src="%s" style="max-width: 50px" alt="%s" />',
            ep_img_url(self::ICON_NAME . '.svg'),
            self::ICON_NAME
        );
        return apply_filters('woocommerce_gateway_icon', $icon_str, $this->id);
    }
}
