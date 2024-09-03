<?php

defined('ABSPATH') || exit;

/**
 * <h2>Subscription FLASHPAY Gate2025 API.</h2>
 *
 * @class    Ep_Gateway_API_Subscription
 * @since    2.0.0
 * @package  Ep_Gateway/Api
 * @category Class
 */
class Ep_Gateway_API_Subscription extends Ep_Gateway_API
{
    /**
     * <h2>Subscription Gate2025 API constructor.</h2>
     *
     * @since 2.0.0
     */
    public function __construct()
    {
        // Run the parent construct
        parent::__construct('payment');
    }

    /**
     * @inheritDoc
     * @since 2.0.0
     * @return void
     */
    protected function hooks()
    {
        parent::hooks();

        add_filter('ep_api_recurring_form_data', [$this, 'create_recurring_request_form_data'], 10, 2);
        add_filter('ep_api_append_recurring_data', [$this, 'append_recurring_data'], 10, 2);
        add_filter('ep_api_recurring_cancel_form_data', [$this, 'create_cancel_request_form_data'], 10, 2);
    }

    /**
     * <h2>Sends data and return created subscription transaction data.</h2>
     *
     * @param int $subscription_id <p>Subscription identifier.</p>
     * @param Ep_Gateway_Order $order <p>Renew subscription order.</p>
     * @param int $amount <p>Amount of renewal subscription.</p>
     * @return Ep_Gateway_Info_Response
     * @throws Ep_Gateway_API_Exception <p>
     * If subscriptions is not enabled or payment_method not supported subscriptions.
     * </p>
     */
    public function recurring($subscription_id, Ep_Gateway_Order $order, $amount = null)
    {
        ep_get_log()->info(__('Run recurring API process.', 'woo-flashpay'));
        ep_get_log()->debug(__('Subscription ID:', 'woo-flashpay'), $subscription_id);
        ep_get_log()->debug(__('Order ID:', 'woo-flashpay'), $order->get_id());
        ep_get_log()->debug(__('Payment status:', 'woo-flashpay'), $order->get_ep_status());

        if (!class_exists('WC_Subscriptions_Order')) {
            ep_get_log()->alert(
                __(
                    'Woocommerce Subscription plugin is not available. Interrupt process.',
                    'woo-flashpay'
                )
            );
            throw new Ep_Gateway_API_Exception(__('Woocommerce Subscription plugin is not available.', 'woo-flashpay'));
        }

        // Check if a custom amount has been set
        if ($amount === null) {
            // No custom amount set. Default to the order total
            $amount = WC_Subscriptions_Order::get_recurring_total($order);
        }

        ep_get_log()->debug(__('Amount:', 'woo-flashpay'), $amount);

        $payment_method = Ep_Gateway_Payment_Methods::get_code($order->get_payment_system());

        if (!$payment_method) {
            throw new Ep_Gateway_API_Exception(__('Payment method is not supported subscription.', 'woo-flashpay'));
        }

        ep_get_log()->debug(__('Payment method:', 'woo-flashpay'), $payment_method);

        // Create form data
        $data = apply_filters('ep_api_recurring_form_data', $subscription_id, $order);

        // Run request
        $response = new Ep_Gateway_Info_Response(
            $this->post(
                sprintf('%s/%s', $payment_method, 'recurring'),
                apply_filters('ep_append_signature', $data)
            )
        );

        ep_get_log()->info(__('Recurring process completed.', 'woo-flashpay'));

        return $response;
    }

    /**
     * <h2>Sends a request and returns the information about the transaction.</h2>
     *
     * @param string $request_id <p>Request identifier.</p>
     * @since 2.0.0
     * @return Ep_Gateway_Info_Response <p>Transaction information data.</p>
     */
    public function operation_status($request_id)
    {
        ep_get_log()->info(__('Run check transaction status API process.', 'woo-flashpay'));
        ep_get_log()->debug(__('Request ID:', 'woo-flashpay'), $request_id);

        // Create form data
        $data = apply_filters('ep_create_general_data', $request_id);
        // Run request
        $response = new Ep_Gateway_Info_Response(
            $this->post(
                'status/request',
                apply_filters('ep_append_signature', $data)
            )
        );

        ep_get_log()->info(__('Check transaction status process completed.', 'woo-flashpay'));
        return $response;
    }

    /**
     * <h2>Sends data and return subscription cancellation data.</h2>
     *
     * @param int $subscription_id <p>Recurring identifier.</p>
     * @param Ep_Gateway_Order $order <p>Cancellation order.</p>
     * @return bool
     * @since 2.0.0
     */
    public function cancel($subscription_id, Ep_Gateway_Order $order)
    {
        ep_get_log()->info(__('Run recurring cancel API process.', 'woo-flashpay'));
        ep_get_log()->debug(__('Subscription ID:', 'woo-flashpay'), $subscription_id);
        ep_get_log()->debug(__('Order ID:', 'woo-flashpay'), $order->get_id());
        ep_get_log()->debug(__('Payment status:', 'woo-flashpay'), $order->get_ep_status());

        return true;
        // todo: get sub_id and send request to cancelled
//        $data = apply_filters('ep_api_recurring_cancel_form_data', $subscription_id, $order);
//        $request_url = sprintf('%s/%s/%s',
//            Ep_Gateway_Payment_Methods::get_code($order->get_payment_system()),
//            'recurring',
//            'cancel'
//        );
//
//        $response = new Ep_Gateway_Info_Response(
//            $this->post($request_url, apply_filters('ep_api_append_signature', $data))
//        );
//
//        ep_get_log()->info(__('Recurring cancel process completed.', 'woo-flashpay'));
//
//        return $response;
    }

    /**
     * <h2>Returns the underlying form data for the recurring request.</h2>
     *
     * @param int $subscription_id <p>FLASHPAY recurring identifier.</p>
     * @param Ep_Gateway_Order $order <p>Renewal subscription order.</p>
     * @since 2.0.0
     * @return array[] <p>Form data for the recurring request.</p>
     */
    final public function create_recurring_request_form_data($subscription_id, Ep_Gateway_Order $order)
    {
        ep_get_log()->info(__('Create form data for recurring request.', 'woo-flashpay'));

        $data = $this->create_general_section(
            apply_filters(
                'ep_append_merchant_callback_url',
                apply_filters('ep_create_general_data', $order)
            )
        );
        $data = apply_filters('ep_api_append_recurring_data', $data, $subscription_id);
        $data = apply_filters('ep_append_payment_section', $data, $order);

        $ip_address = $order->get_ep_meta('_customer_ip_address');
        $data['customer'] = [
            'id' => (string) $order->get_customer_id(),
            "ip_address" => $ip_address ?: wc_get_var($_SERVER['REMOTE_ADDR'])
        ];

        return apply_filters('ep_append_interface_type', $data);
    }

    /**
     * <h2>Returns the underlying form data for the recurring cancel request.</h2>
     *
     * @param int $subscription_id <p>FLASHPAY recurring identifier.</p>
     * @param Ep_Gateway_Order $order <p>Renewal subscription order.</p>
     * @since 2.0.0
     * @return array <p>Form data for the cancel recurring request.</p>
     */
    final public function create_cancel_request_form_data($subscription_id, Ep_Gateway_Order $order)
    {
        ep_get_log()->info(__('Create form data for recurring cancel request.', 'woo-flashpay'));

        return apply_filters(
            'ep_append_interface_type',
            $this->create_general_section(
                apply_filters(
                    'ep_api_append_recurring_data',
                    apply_filters(
                        'ep_append_merchant_callback_url',
                        apply_filters('ep_create_general_data', $order)
                    ),
                    $subscription_id
                )
            )
        );
    }

    /**
     * <h2>Append recurring information to the form data.</h2>
     *
     * @param array $data <p>Form data as array.</p>
     * @param string $subscription_id <p>Identifier of the subscription.</p>
     * @since 3.0.0
     * @return array <p>Form data with recurring information.</p>
     */
    public function append_recurring_data($data, $subscription_id)
    {
        $data['recurring'] = ['id' => $subscription_id];
        $data['recurring_id'] = $subscription_id;

        return $data;
    }
}
