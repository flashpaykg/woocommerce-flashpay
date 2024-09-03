<?php

class Ep_Gateway_Payment_Provider extends Ep_Gateway_Registry
{
    const TRANSIENT_PREFIX = 'wc_ep_transition_';

    /**
     * Fetches transaction data based on a transaction ID. This method checks if the transaction is cached in a
     * transient before it asks the FLASHPAY API. Cached data will always be used if available.
     *
     * If no data is cached, we will fetch the transaction from the API and cache it.
     *
     * @param Ep_Gateway_Order $order
     * @param bool $reload
     * @return Ep_Gateway_Payment
     */
    public function load(Ep_Gateway_Order $order, $reload = false)
    {
        ep_get_log()->debug(__('Loading payment information...', 'woo-flashpay'));
        ep_get_log()->debug(__('Order ID:', 'woo-flashpay'), $order->get_id());
        ep_get_log()->debug(__('Reload?', 'woo-flashpay'), $reload ? __('Yes', 'woo-flashpay') : __('No', 'woo-flashpay'));

        if (!$reload && $this->is_transaction_caching_enabled()) {
            ep_get_log()->info(__('Try loading payment data from cache...', 'woo-flashpay'));
            $transient = get_transient($this->get_transient_id($order->get_payment_id()));

            if ($transient) {
                // new Ep_Gateway_Info_Status(json_decode($transient, true))
                $payment = @unserialize($transient);

                if ($payment instanceof Ep_Gateway_Payment) {
                    ep_get_log()->info(__('Payment loaded from cache. Cache data exists.', 'woo-flashpay'));
                    return $payment;
                }

                ep_get_log()->warning(__('Cache data corrupted:', 'woo-flashpay'), $transient);
            } else {
                ep_get_log()->info(__('Invalid cache data.', 'woo-flashpay'));
            }
        }

        if ($order->get_ep_status() === Ep_Gateway_Payment_Status::INITIAL) {
            ep_get_log()->info(__('Payment is initial. Initialize blank payment data.', 'woo-flashpay'));
            $payment = Ep_Gateway_Payment::stub($order);
        } else {
            ep_get_log()->info(__('Get payment data from FLASHPAY.', 'woo-flashpay'));
            $payment = $this->reload($order);
        }

        ep_get_log()->info(__('Payment information loaded:', 'woo-flashpay'), $order->get_payment_id());

        if ($this->is_transaction_caching_enabled()) {
            $payment->save();
        }

        return $payment;
    }

    /**
     * <h2>Stores payment details to the cache.</h2>
     *
     * @param Ep_Gateway_Payment $payment
     * @return void
     */
    public function save(Ep_Gateway_Payment $payment)
    {
        ep_get_log()->debug(__('Save payment:', 'woo-flashpay'), $payment->get_id());
        $payment->status_transition();

        if (!$this->is_transaction_caching_enabled()) {
            ep_get_log()->info(__('Cache disabled. Cancelled store payment details.', 'woo-flashpay'));
            return;
        }

        try {
            $expiration = (int) flashpay()->get_general_option(
                Ep_Gateway_Settings_General::OPTION_CACHING_EXPIRATION,
                7 * DAY_IN_SECONDS
            );

            // Cache expiration in seconds
            $expiration = apply_filters('woocommerce_flashpay_transaction_cache_expiration', $expiration);

            ep_get_log()->debug(__('Expiration length:.', 'woo-flashpay'), $expiration);
            set_transient(
                $this->get_transient_id($payment->get_id()),
                serialize($payment),
                $expiration
            );
        } catch (Exception $e) {
            ep_get_log()->error(__('Error saving payment ', 'woo-flashpay'), $payment->get_id());
            $payment->get_order()->add_order_note(__('Error saving payment.', 'woocommerce') . ' ' . $e->getMessage());
        }

        ep_get_log()->info(__('Payment details successfully saved.', 'woo-flashpay'), $payment->get_id());
    }

    /**
     * @param Ep_Gateway_Order $order
     * @return Ep_Gateway_Payment
     */
    private function reload(Ep_Gateway_Order $order)
    {
        $api = new Ep_Gateway_API_Payment();
        $status = $api->status($order);
        $payment = new Ep_Gateway_Payment($order);

        if (count($status->get_errors()) > 0) {
            if ($status->try_get_payment($info)) {
                $payment->set_info($info);
            }
            return $payment;
        }

        return $payment->set_info($status->get_payment())
            ->set_customer($status->get_customer())
            ->set_acs($status->get_acs())
            ->set_account($status->get_account())
            ->set_operations($status->get_operations());
    }

    /**
     * @return boolean
     */
    private function is_transaction_caching_enabled()
    {
        return apply_filters(
            'ep_transaction_cache_enabled',
            ep_is_enabled(Ep_Gateway_Settings_General::OPTION_CACHING_ENABLED)
        );
    }

    private function get_transient_id($id)
    {
        return self::TRANSIENT_PREFIX . $id;
    }
}