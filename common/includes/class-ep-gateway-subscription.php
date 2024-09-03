<?php

defined('ABSPATH') || exit;

/**
 * Ep_Gateway_Subscription
 *
 * Extends Woocommerce subscription for easy access to internal data.
 *
 * @class    Ep_Gateway_Subscription
 * @version  2.0.0
 * @package  Ep_Gateway/Includes
 * @category Class
 */
class Ep_Gateway_Subscription extends WC_Subscription
{
    use EP_Gateway_Order_Extension;

    public function set_recurring_id($recurring_id)
    {
        $this->set_ep_meta('_flashpay_recurring_id', $recurring_id);
    }

    /**
     * <h2>Returns recurring identifier.</h2>
     *
     * @return int <p>Recurring identifier.</p>
     */
    public function get_recurring_id()
    {
        return (int) $this->get_ep_meta('_flashpay_recurring_id');
    }

    /**
     * <h2>Returns the parent order from the subscription.</h2>
     * @param int $id [optional] <i>** Unusable **</i>
     * @return ?Ep_Gateway_Order Parent order if exists or <b>NULL</b> otherwise.
     * @since 2.0.0
     * @uses ep_get_order()
     */
    public function get_order($id = 0)
    {
        return ep_get_order($this->get_parent_id());
    }
}