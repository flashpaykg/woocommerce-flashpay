<?php

defined('ABSPATH') || exit;

/**
 * <h2>Callback handler.</h2>
 *
 * @class    Ep_Gateway_Callbacks
 * @version  2.0.0
 * @package  Ep_Gateway/Includes
 * @category Class
 * @internal
 */
class Ep_Gateway_Callbacks
{
    /**
     * <h2> List of supported operations.</h2>
     *
     * @var string[]
     * @since 2.0.0
     */
    private $operations = [
        Ep_Gateway_Operation_Type::SALE => 'woocommerce_flashpay_callback_sale',
        Ep_Gateway_Operation_Type::REFUND => 'woocommerce_flashpay_callback_refund',
        Ep_Gateway_Operation_Type::REVERSAL => 'woocommerce_flashpay_callback_reversal',
        Ep_Gateway_Operation_Type::RECURRING => 'woocommerce_flashpay_callback_recurring',
        Ep_Gateway_Operation_Type::ACCOUNT_VERIFICATION => 'woocommerce_flashpay_callback_verify',
        Ep_Gateway_Operation_Type::RECURRING_CANCEL => 'woocommerce_flashpay_callback_recurring_cancel',
        Ep_Gateway_Operation_Type::PAYMENT_CONFIRMATION => 'woocommerce_flashpay_callback_payment_confirmation',
    ];

    /**
     * <h2>Callback handler constructor.</h2>
     *
     * @param array $data <p>Callback data.</p>
     * @since 2.0.0
     */
    private function __construct(array $data)
    {
        add_action('woocommerce_flashpay_callback_refund', [Ep_Gateway_Module_Refund::get_instance(), 'handle'], 10, 2);
        add_action('woocommerce_flashpay_callback_reversal', [Ep_Gateway_Module_Refund::get_instance(), 'handle'], 10, 2);
        add_action('woocommerce_flashpay_callback_sale', [$this, 'sale'], 10, 2);
        add_action('woocommerce_flashpay_callback_recurring', [$this, 'recurring'], 10, 2);
        add_action('woocommerce_flashpay_callback_verify', [$this, 'verify'], 10, 2);
        add_action('woocommerce_flashpay_callback_payment_confirmation', [$this, 'confirm'], 10, 2);

        // Decode the body into JSON
        $info = new Ep_Gateway_Info_Callback($data);

        // Instantiate order object
        $order = $this->get_order($info);

        // Execute callback process.
        $this->processor($info, $order);
    }

    public static function handle()
    {
        ep_get_log()->info(_x('Run callback handler.', 'Log information', 'woo-flashpay'));

        // Get callback body
        $body = file_get_contents('php://input');

        $data = json_decode($body, true);

        ep_get_log()->debug(__('Incoming callback data:', 'woo-flashpay'), $body);

        // Check signature
        self::check_signature($data);

        return new static($data);
    }

    /**
     * @param Ep_Gateway_Order $order
     * @param Ep_Gateway_Info_Callback $callback
     * @return void
     * @since 2.0.0
     */
    public function processor($callback, $order)
    {
        ep_get_log()->info(__('Run callback processor', 'woo-flashpay'));

        do_action('ep_accepted_callback_before_processing', $order, $callback);
        do_action('ep_accepted_callback_before_processing_' . $callback->get_operation()->get_type(), $order, $callback);

        // Clear card - payment is not initial.
        WC()->cart->empty_cart();

        if (array_key_exists($callback->get_operation()->get_type(), $this->operations)) {
            do_action($this->operations[$callback->get_operation()->get_type()], $callback, $order);
            $message = 'OK';
        } else {
            $message = sprintf(
                __('Not supported operation type: %s', 'woo-flashpay'),
                $callback->get_operation()->get_type()
            );
            ep_get_log()->warning($message);
        }

        do_action('ep_accepted_callback_after_processing', $order, $callback);
        do_action('ep_accepted_callback_after_processing_' . $callback->get_operation()->get_type(), $order, $callback);

        http_response_code(200);
        die ($message);
    }

    /**
     * @param Ep_Gateway_Info_Callback $callback
     * @param Ep_Gateway_Order $order
     * @return void
     * @throws WC_Data_Exception
     */
    public function verify($callback, $order)
    {
        ep_get_log()->info(__('Apply verify callback data.', 'woo-flashpay'));
        $this->log_order_data($order);

        // Set the transaction order ID
        $this->update_payment($order, $callback);

        $order->set_transaction_order_id($callback->get_operation()->get_request_id());
        $order->set_payment_system($callback->get_payment()->get_method());
        $this->update_subscription($order, $callback);
        $this->process($callback, $order);
    }

    /**
     * @param Ep_Gateway_Info_Callback $callback
     * @param Ep_Gateway_Order $order
     * @return void
     * @throws WC_Data_Exception
     */
    public function confirm($callback, $order)
    {
        ep_get_log()->info(__('Apply payment confirmation callback data.', 'woo-flashpay'));
        $this->log_order_data($order);

        // Set the transaction order ID
        $this->update_payment($order, $callback);
        $order->set_payment_system($callback->get_payment()->get_method());
        $this->update_subscription($order, $callback);
        $this->process($callback, $order);
    }

    /**
     * @param Ep_Gateway_Info_Callback $callback
     * @param Ep_Gateway_Order $order
     * @throws WC_Data_Exception
     */
    public function recurring($callback, $order)
    {
        ep_get_log()->info(__('Apply recurring callback data.', 'woo-flashpay'));
        $this->log_order_data($order);

        // Set the transaction order ID
        $this->update_payment($order, $callback);
        $order->set_payment_system($callback->get_payment()->get_method());
        $this->process($callback, $order);
    }

    /**
     * @param Ep_Gateway_Info_Callback $callback
     * @param Ep_Gateway_Order $order
     * @throws WC_Data_Exception
     */
    public function sale($callback, $order)
    {
        ep_get_log()->info(__('Apply sale callback data.', 'woo-flashpay'));
        $this->log_order_data($order);

        // Set the transaction order ID
        $this->update_payment($order, $callback);
        $this->update_subscription($order, $callback);
        $order->set_payment_system($callback->get_payment()->get_method());
        $this->process($callback, $order);
    }

    private function log_order_data(Ep_Gateway_Order $order)
    {
        ep_get_log()->debug(__('Order ID:', 'woo-flashpay'), $order->get_id());
        ep_get_log()->debug(__('Payment ID:', 'woo-flashpay'), $order->get_payment_id());
        ep_get_log()->debug(__('Transaction ID:', 'woo-flashpay'), $order->get_ep_transaction_id());
    }

    /**
     * @param Ep_Gateway_Info_Callback $callback
     * @param Ep_Gateway_Order $order
     * @return void
     * @throws WC_Data_Exception
     */
    private function process(Ep_Gateway_Info_Callback $callback, Ep_Gateway_Order $order)
    {
        switch ($callback->get_payment()->get_status()) {
            case Ep_Gateway_Operation_Status::AWAITING_CONFIRMATION:
                $this->hold_order($callback, $order);
                break;
            case Ep_Gateway_Operation_Status::AWAITING_CUSTOMER:
                $this->decline_order($callback, $order);
                break;
            case Ep_Gateway_Operation_Status::EXTERNAL_PROCESSING:
                break;
            default:
                $this->processOperation($callback, $order);
                break;
        }
    }

    /**
     * @param Ep_Gateway_Info_Callback $callback
     * @param Ep_Gateway_Order $order
     * @return void
     * @throws WC_Data_Exception
     */
    private function processOperation(Ep_Gateway_Info_Callback $callback, Ep_Gateway_Order $order)
    {
        switch ($callback->get_operation()->get_status()) {
            case Ep_Gateway_Operation_Status::SUCCESS:
                $this->complete_order($callback, $order);
                break;
            case Ep_Gateway_Operation_Status::DECLINE:
            case Ep_Gateway_Operation_Status::EXPIRED:
            case Ep_Gateway_Operation_Status::INTERNAL_ERROR:
            case Ep_Gateway_Operation_Status::EXTERNAL_ERROR:
                $this->decline_order($callback, $order);
                break;
        }
    }

    /**
     * @param Ep_Gateway_Info_Callback $callback
     * @param Ep_Gateway_Order $order
     * @return void
     * @throws WC_Data_Exception
     */
    private function hold_order(Ep_Gateway_Info_Callback $callback, Ep_Gateway_Order $order)
    {
        ep_get_log()->debug(__('Run awaiting confirmation process.', 'woo-flashpay'), $order->get_id());
        $order->set_transaction_id($callback->get_operation()->get_request_id());
        $order->update_status('on-hold');
        ep_get_log()->debug(__('Awaiting confirmation process completed.', 'woo-flashpay'), $order->get_id());
    }

    /**
     * @param Ep_Gateway_Info_Callback $callback
     * @param Ep_Gateway_Order $order
     * @return void
     */
    private function complete_order(Ep_Gateway_Info_Callback $callback, Ep_Gateway_Order $order)
    {
        $order_currency = $order->get_currency_uppercase();
        $payment_currency = $callback->get_payment_currency();

        $is_amount_equal = $order->get_total_minor() === $callback->get_payment_amount_minor();
        $is_currency_equal = $order_currency === $payment_currency;

        ep_get_log()->debug(__('Run success process.', 'woo-flashpay'), $order->get_id());
        $order->payment_complete($callback->get_operation()->get_request_id());
        ep_get_log()->debug(__('Success process completed.', 'woo-flashpay'), $order->get_id());

        if (!$is_amount_equal || !$is_currency_equal) {
            $message = sprintf(
                'The payment amount does not match the order amount. The order has %s %s. The payment has %s %s', 
                $order->get_total(), $order_currency, $callback->get_payment_amount(), $payment_currency
            );
            $order->add_order_note(__($message, 'woo-flashpay'));
        }
    }

    /**
     * @param Ep_Gateway_Info_Callback $callback
     * @param Ep_Gateway_Order $order
     * @return void
     * @throws WC_Data_Exception
     */
    private function decline_order(Ep_Gateway_Info_Callback $callback, Ep_Gateway_Order $order)
    {
        ep_get_log()->debug(__('Run failed process.', 'woo-flashpay'), $order->get_id());
        $order->set_transaction_id($callback->get_operation()->get_request_id());
        $order->update_status('failed');
        $order->increase_failed_flashpay_payment_count();
        ep_get_log()->debug(__('Failed process completed.', 'woo-flashpay'), $order->get_id());
    }

    /**
     * @param $data
     * @return void
     */
    private static function check_signature($data)
    {
        ep_get_log()->debug(__('Verify signature', 'woo-flashpay'));
        try {
            if (!ep_check_signature($data)) {
                $message = _x('Invalid callback signature.', 'Error message', 'woo-flashpay');
                ep_get_log()->error($message);

                http_response_code(400);
                die ($message);
            }

            ep_get_log()->debug(__('Signature verified.', 'woo-flashpay'));
        } catch (Ep_Gateway_Signature_Exception $e) {
            $e->write_to_logs();
            http_response_code(500);
            die ($e->getMessage());
        }
    }

    /**
     * <h2>Returns order by callback information.</h2>
     *
     * @param Ep_Gateway_Info_Callback $info <p>Callback information.</p>
     * @since 2.0.0
     * @return Ep_Gateway_Order <p>Payment order.</p>
     */
    private function get_order($info)
    {
        // Fetch order number;
        $order_number = Ep_Gateway_Order::get_order_id_from_callback($info, Ep_Core::CMS_PREFIX);
        $order = ep_get_order($order_number);

        if (!$order) {
            // Print debug information to logs
            $message = __('Order not found', 'woo-flashpay');
            ep_get_log()->error($message);
            ep_get_log()->info(__('Transaction failed for', 'woo-flashpay'), $order_number);

            foreach ($info->get_errors() as $error) {
                ep_get_log()->add(__('Error code:', 'woo-flashpay'), $error->get_code());
                ep_get_log()->add(__('Error field:', 'woo-flashpay'), $error->get_field());
                ep_get_log()->add(__('Error message:', 'woo-flashpay'), $error->get_message());
                ep_get_log()->add(__('Error description:', 'woo-flashpay'), $error->get_description());
            }

            ep_get_log()->add(__('Response data: %s', 'woo-flashpay'), json_encode($info));

            http_response_code(404);
            die ($message);
        }

        return $order;
    }

    /**
     * <h2>Update payment data.</h2>
     *
     * @param Ep_Gateway_Order $order <p>Payment order.</p>
     * @param Ep_Gateway_Info_Callback $callback <p>Callback information.</p>
     * @since 2.0.0
     * @return void
     */
    private function update_payment($order, $callback)
    {
        $payment = $order->get_payment();
        $payment->add_operation($callback->get_operation());
        $payment->set_info($callback->get_payment());
        $payment->save();
    }

    /**
     * <h2>Sets to subscriptions recurring information.</h2>
     *
     * @param Ep_Gateway_Order $order <p>Parent payment order.</p>
     * @param Ep_Gateway_Info_Callback $callback <p>Callback information.</p>
     * @since 2.0.0
     * @return void
     */
    private function update_subscription($order, $callback)
    {
        if ($order->contains_subscription()) {
            ep_get_log()->debug(__('Order has subscriptions', 'woo-flashpay'));
            $subscriptions = $order->get_subscriptions();

            if (count($subscriptions) <= 0) {
                return;
            }

            if (!$callback->try_get_recurring($recurring)) {
                ep_get_log()->critical(
                    __('No recurring information found in callback data. The Subscription cannot be renewed.', 'woo-flashpay')
                );
                return;
            }

            ep_get_log()->debug(__('Recurring ID:', 'woo-flashpay'), $recurring->get_id());

            foreach ($subscriptions as $subscription) {
                ep_get_log()->debug(__('Subscription ID:', 'woo-flashpay'), $subscription->get_id());
                $subscription->set_recurring_id($callback->get_recurring()->get_id());
                $subscription->save();
            }
        }
    }
}
