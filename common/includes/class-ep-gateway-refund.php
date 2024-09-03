<?php

defined('ABSPATH') || exit;

/**
 * Ep_Gateway_Refund
 *
 * Extends Woocommerce refund for easy access to internal data.
 *
 * @class    Ep_Gateway_Refund
 * @version  2.0.0
 * @package  Ep_Gateway/Includes
 * @category Class
 */
class Ep_Gateway_Refund extends \Automattic\WooCommerce\Admin\Overrides\OrderRefund
{
    use EP_Gateway_Order_Extension;

    /**
     * <h2>Creates and returns a new FLASHPAY refund identifier.</h2>
     *
     * @return string
     * @since 2.0.0
     * @uses Ep_Gateway_Refund::set_is_test()
     * @uses Ep_Gateway_Refund::get_id()
     * @uses Ep_Gateway_Refund::set_payment_id()
     * @uses Ep_Gateway_Refund::set_ep_status()
     * @uses Ep_Gateway_Order::get_refund_attempts_count()
     * @uses Ep_Gateway_Order::increase_refund_attempts_count()
     */
    public function create_payment_id()
    {
        $order = ep_get_order($this->get_parent_id());
        $test_mode = ep_is_enabled(Ep_Gateway_Settings_General::OPTION_TEST);

        if ($test_mode) {
            $id = Ep_Core::CMS_PREFIX . '&' . wc_get_var($_SERVER['SERVER_NAME'], 'undefined') . '&';
            $this->set_is_test();
        } else {
            $id = '';
        }

        $id .= $this->get_id() . '_' . ($order->get_refund_attempts_count() + 1);
        $order->increase_refund_attempts_count();
        $order->save();

        $this->set_payment_id($id);
        $this->set_ep_status('initial');
        $this->save();

        ep_get_log()->debug(__('New refund payment identifier created:', 'woo-flashpay'), $id);
        return $id;
    }

    /**
     * <h2>Returns the parent order from the refund.</h2>
     * @param int $id [optional] <i>** Unusable **</i>
     * @return ?Ep_Gateway_Order Parent order if exists or <b>NULL</b> otherwise.
     * @since 2.0.0
     * @uses ep_get_order()
     */
    public function get_order($id = 0)
    {
        return ep_get_order($this->get_parent_id());
    }

    /**
     * <h2>Updates status of refund immediately.</h2>
     *
     * @param string $new_status <p>Status to change the refund to.</p>
     * @param string $note [optional] <b>Note to add. Default: blank string.</p>
     * @return bool <b>TRUE</b> on status changed or <b>FALSE</b> otherwise.
     * @since 2.0.0
     * @uses Ep_Gateway_Refund::set_ep_status()
     * @uses Ep_Gateway_Refund::add_comment()
     */
    public function update_status($new_status, $note = '')
    {
        ep_get_log()->debug(__('Update refund status.', 'woo-flashpay'));

        if (!$this->get_id()) {
            ep_get_log()->warning(__('Undefined identifier for refund object.', 'woo-flashpay'));
            return false;
        }

        try {
            $this->set_ep_status($new_status, $note);
            $this->save();
        } catch (Exception $e) {
            ep_get_log()->error(
                sprintf(__('Error updating status for refund #%d', 'woo-flashpay'), $this->get_id())
            );
            ep_get_log()->error($e->getMessage());
            return false;
        }

        ep_get_log()->debug(__('Refund payment status updated.', 'woo-flashpay'));
        return true;
    }

    /**
     * <h2>Returns the refund status from the FLASHPAY payment platform.</h2>
     *
     * @param string $context <p>What the value is for. Valid values are view and edit.</p>
     * @return string
     * @since 2.0.0
     */
    public function get_ep_status($context = 'view')
    {
        return $this->get_ep_meta('_refund_status', true, $context);
    }

    /**
     * <h2>Set refund status from the FLASHPAY payment platform.</h2>
     *
     * @param string $status <p>Status to change the refund to.</p>
     * @return array Details of change
     * @since 2.0.0
     */
    public function set_ep_status($status, $note = '')
    {
        ep_get_log()->debug(__('Transition refund FLASHPAY status', 'woo-flashpay'));

        $old = $this->get_ep_status();

        if ($status === $old) {
            ep_get_log()->notice(__('Refund statuses from and to identical. Skip process.'));
            ep_get_log()->debug(__('Refund status:', 'woo-flashpay'), $old);
            return ['from' => $old, 'to' => $status];
        }

        if ($old !== '' && !in_array($old, $this->get_valid_ep_statuses())) {
            ep_get_log()->warning(sprintf(__('Refund form status "%s" is not supported', 'woo-flashpay'), $old));
            $old = 'initial';
        }

        if (!in_array($status, $this->get_valid_ep_statuses())) {
            ep_get_log()->warning(sprintf(__('Refund to status "%s" is not supported', 'woo-flashpay'), $old));
            $old = 'initial';
        }

        $this->set_ep_meta('_refund_status', $status);
        $transition = ['from' => $old, 'to' => $status];

        if ($note !== '') {
            $this->add_comment($note);
        }

        ep_get_log()->info(sprintf(__('Refund status transitions: [%s] => [%s]', 'woo-flashpay'), $old, $status));

        return $transition;
    }

    /**
     * <h2>Adds the comment in current refund reason.</h2>
     *
     * @param string $comment [optional] <p>Comment. Default: blank string.</p>
     * @return void
     * @since 2.0.0
     * @uses Ep_Gateway_Refund::get_reason()
     * @uses Ep_Gateway_Refund::set_reason()
     * @uses Ep_Gateway_Refund::get_id()
     * @uses Ep_Gateway_Refund::get_parent_id()
     * @uses Ep_Gateway_Refund::get_order()
     */
    private function add_comment($comment = '')
    {
        // Return if the comment is blank
        if ($comment === '') {
            return;
        }

        ep_get_log()->debug(__('Add comment into refund', 'woo-flashpay'));
        ep_get_log()->debug(__('Comment:', 'woo-flashpay'), $comment);
        $reason = $this->get_reason();

        if (empty ($reason)) {
            $reason = $comment;
        } else {
            $reason .= ' | ' . $comment;
        }

        try {
            $this->set_reason($reason);

            $refund_request_comment_id = $this->get_ep_meta('_refund_request_comment_id');

            if ($refund_request_comment_id) {
                $this->get_order()->append_order_comment($comment, $refund_request_comment_id);
            }

            ep_get_log()->info(__('Comment added to refund', 'woo-flashpay'));
        } catch (Exception $e) {
            ep_get_log()->error(__('', 'woo-flashpay'));
            ep_get_log()->error($e->getMessage());
        }
    }

    /**
     * <h2>Returns all valid statuses for this refund.</h2>
     *
     * @return array <p>Internal status keys.</p>
     * @since 2.0.0
     */
    private function get_valid_ep_statuses()
    {
        return ['initial', 'completed', 'failed'];
    }
}