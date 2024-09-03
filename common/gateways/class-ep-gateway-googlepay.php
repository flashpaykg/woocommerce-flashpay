<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * <h2>FLASHPAY Gateway GooglePay.</h2>
 *
 * @class    Ep_Gateway_Googlepay
 * @version  3.0.1
 * @package  Ep_Gateway/Gateways
 * @category Class
 */
class Ep_Gateway_Googlepay extends Ep_Gateway
{
    const PAYMENT_METHOD = 'google_pay_host';

    // region Properties
    /**
     * @inheritDoc
     * @override
     * @var string[]
     * @since 3.0.1
     */
    public $supports = [
        'products',
        'refunds',
        'subscriptions',
        'subscription_cancellation',
        'subscription_reactivation',
        'subscription_suspension',
        'subscription_amount_changes',
        'subscription_date_changes',
        'multiple_subscriptions',
    ];

    /**
     * <h2>Instance of FLASHPAY GooglePay Gateway.</h2>
     *
     * @var Ep_Gateway
     * @since 3.0.1
     */
    private static $_instance;

    // endregion

    // region Static methods

    /**
     * <h2>Returns a new instance of self, if it does not already exist.</h2>
     *
     * @return static
     * @since 3.0.1
     */
    public static function get_instance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }
    // endregion

    /**
     * <h2>FLASHPAY GooglePay Gateway constructor.</h2>
     */
    public function __construct()
    {
        $this->id = Ep_Gateway_Settings_Googlepay::ID;
        $this->method_title = __('FLASHPAY GooglePay', 'woo-flashpay');
        $this->method_description = __('Accept payments via GooglePay.', 'woo-flashpay');
        $this->has_fields = false;
        $this->title = $this->get_option(Ep_Gateway_Settings::OPTION_TITLE);
        $this->order_button_text = $this->get_option(Ep_Gateway_Settings::OPTION_CHECKOUT_BUTTON_TEXT);
        $this->enabled = $this->get_option(Ep_Gateway_Settings::OPTION_ENABLED);
        $this->icon = $this->get_icon();

        if ($this->is_enabled(Ep_Gateway_Settings::OPTION_SHOW_DESCRIPTION)) {
            $this->description = $this->get_option(Ep_Gateway_Settings::OPTION_DESCRIPTION);
        }

        parent::__construct();

        $this->init_subscription();
    }

    /**
     * @inheritDoc
     * @override
     * @return array
     * @since 3.0.1
     */
    public function apply_payment_args($values, $order)
    {
        $amount = ep_price_multiply($order->get_total(), $order->get_currency());

        $values = apply_filters('ep_append_operation_mode', $values, $amount > 0 ? 'purchase' : 'card_verify');
        $values = apply_filters('ep_append_force_mode', $values, self::PAYMENT_METHOD);
        $values = apply_filters('ep_append_recurring', $values, $order);

        return parent::apply_payment_args($values, $order);
    }

    /**
     * @return string
     * @since 3.0.1
     */
    public function get_refund_endpoint($order)
    {
        return Ep_Gateway_Payment_Methods::get_code($order);
    }

    /**
     * @inheritDoc
     * @override
     * @return array <p>Settings for redirecting to the FLASHPAY payment page.</p>
     * @since 3.0.1
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
     * @since 3.0.1
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
     * @since 3.0.1
     */
    public function can_refund_order($order)
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

    private function init_subscription()
    {
        // WooCommerce Subscriptions hooks/filters
        if (!ep_subscription_is_active()) {
            return;
        }

        // On scheduled subscription
        add_action(
            'woocommerce_scheduled_subscription_payment_' . $this->id,
            [WC_Gateway_Flashpay_Module_Subscription::get_instance(), 'scheduled_subscription_payment'],
            10,
            2
        );

        // On cancelled subscription
        add_action(
            'woocommerce_subscription_cancelled_' . $this->id,
            [WC_Gateway_Flashpay_Module_Subscription::get_instance(), 'subscription_cancellation']
        );

        // On updated subscription
        add_action(
            'woocommerce_subscription_payment_method_updated_to_' . $this->id,
            [WC_Gateway_Flashpay_Module_Subscription::get_instance(), 'on_subscription_payment_method_updated_to_flashpay'],
            10,
            2
        );

        add_action(
            'woocommerce_subscription_validate_payment_meta_' . $this->id,
            [WC_Gateway_Flashpay_Module_Subscription::get_instance(), 'woocommerce_subscription_validate_payment_meta'],
            10,
            2
        );
    }


    /**
     * @inheritDoc
     * @override
     * @return string DOM element img as a string
     * @since 3.0.1
     */
    public function get_icon()
    {
        $icon_str = '<img src="' . ep_img_url(self::PAYMENT_METHOD . '.svg')
            . '" style="max-width: 50px" alt="' . self::PAYMENT_METHOD . '" />';

        return apply_filters('woocommerce_gateway_icon', $icon_str, $this->id);
    }
}