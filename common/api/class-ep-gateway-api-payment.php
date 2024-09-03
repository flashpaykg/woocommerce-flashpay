<?php

defined('ABSPATH') || exit;

/**
 * <h2>Payment FLASHPAY Gate2025 API.</h2>
 *
 * @class    Ep_Gateway_API_Payment
 * @since    2.0.0
 * @package  Ep_Gateway/Api
 * @category Class
 */
class Ep_Gateway_API_Payment extends Ep_Gateway_API
{
    /**
     * <h2>Payment Gate2025 API constructor.</h2>
     *
     * @since 2.0.0
     */
    public function __construct()
    {
        parent::__construct('payment');
    }

    /**
     * <h2>Sends a request and returns information about the payment.</h2>
     *
     * @param Ep_Gateway_Order $order <p>Order for request.</p>
     * @since 2.0.0
     * @return Ep_Gateway_Info_Status <p>Payment status information.</p>
     */
    public function status(Ep_Gateway_Order $order)
    {
        ep_get_log()->info(__('Run check payment status API process.', 'woo-flashpay'));
        ep_get_log()->debug(__('Order ID:', 'woo-flashpay'), $order->get_id());
        ep_get_log()->debug(__('Current payment status:', 'woo-flashpay'), $order->get_ep_status());
        ep_get_log()->debug(__('Payment method:', 'woo-flashpay'), $order->get_payment_system());

        if ($order->get_ep_status() === Ep_Gateway_Payment_Status::INITIAL) {
            return new Ep_Gateway_Info_Status();
        }

        // Run request
        $response = new Ep_Gateway_Info_Status(
            $this->post(
                'status',
                apply_filters('ep_append_signature', $this->create_status_request_form_data($order))
            )
        );

        ep_get_log()->info(__('Check payment status process completed.', 'woo-flashpay'));

        return $response;
    }

    /**
     * <h2>Sends data and return created refund transaction data.</h2>
     *
     * @param Ep_Gateway_Refund $refund <p>Refund object.</p>
     * @param Ep_Gateway_Order $order <p>Refunding order.</p>
     */
    public function refund(Ep_Gateway_Refund $refund, Ep_Gateway_Order $order)
    {
        ep_get_log()->info(__('Run refund payment API process.', 'woo-flashpay'));
        ep_get_log()->debug(__('Refund ID:', 'woo-flashpay'), $refund->get_id());
        ep_get_log()->debug(__('Order ID:', 'woo-flashpay'), $order->get_id());

        // Create form data
        $data = $this->create_refund_request_form_data($refund, $order);

        /** @var array $variables */
        $variables = flashpay()->get_general_option(Ep_Gateway_Settings_General::OPTION_CUSTOM_VARIABLES, []);

        if (array_search(Ep_Gateway_Settings_General::CUSTOM_RECEIPT_DATA, $variables, true)) {
            // Append receipt data
            $data = apply_filters('ep_append_receipt_data', $data, $refund);
        }

        // Run request
        $response = new Ep_Gateway_Info_Response(
            $this->post(
                sprintf(
                    '%s/%s',
                    apply_filters('ep_api_refund_endpoint_' . $order->get_payment_method(), $order->get_payment_system()),
                    'refund'
                ),
                apply_filters('ep_append_signature', $data)
            )
        );

        ep_get_log()->info(__('Refund payment process completed.', 'woo-flashpay'));

        return $response;
    }

    /**
     * <h2>Returns the underlying form data for the status request.</h2>
     *
     * @param EP_Gateway_Order_Extension $order <p>Order with payment.</p>
     * @since 3.0.0
     * @return array[] <p>Basic form-data.</p>
     */
    private function create_status_request_form_data($order)
    {
        ep_get_log()->info(__('Create form data for status request.', 'woo-flashpay'));
        $data = $this->create_general_section(
            apply_filters(
                'ep_append_merchant_callback_url',
                apply_filters('ep_create_general_data', $order)
            )
        );
        $data['destination'] = 'merchant';

        return $data;
    }

    /**
     * <h2>Returns the underlying form data for the refund request.</h2>
     *
     * @param Ep_Gateway_Refund $refund <p>Refund object.</p>
     * @param Ep_Gateway_Order $order <p>Refunding order.</p>
     * @since 3.0.0
     * @return array[] <p>Basic form-data.</p>
     */
    final public function create_refund_request_form_data(Ep_Gateway_Refund $refund, Ep_Gateway_Order $order)
    {
        ep_get_log()->info(__('Create form data for refund request.', 'woo-flashpay'));
        $data = $this->create_general_section(
            apply_filters(
                'ep_append_merchant_callback_url',
                apply_filters('ep_create_payment_data', $order)
            )
        );
        $data = apply_filters('ep_append_payment_section', $data, $refund);

        return apply_filters('ep_append_interface_type', $data);
    }
}
