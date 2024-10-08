<?php

defined('ABSPATH') || exit;

/**
 * <h2>Administration User Interface addon.</h2>
 *
 * @class    WC_Gateway_Flashpay_Module_Admin_UI
 * @version  2.0.0
 * @package  WC_Gateway_Flashpay/Modules
 * @category Class
 */
class Ep_Gateway_Module_Admin_UI extends Ep_Gateway_Registry
{
    /**
     * @inherit
     * @since 2.0.0
     * @return void
     */
    protected function init()
    {
        // Add internal actions
        add_action('init', 'ep_load_i18n');
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_javascript_backend']);
        add_action('admin_notices', [Ep_Gateway_Install::get_instance(), 'show_update_warning']);

        // Add WooCommerce actions.
        add_action('wp_ajax_flashpay_manual_transaction_actions', [$this, 'ajax_manual_request_actions']);
        add_action('wp_ajax_flashpay_empty_logs', [$this, 'ajax_clear_log']);
        add_action('wp_ajax_flashpay_flush_cache', [$this, 'ajax_flush_payment_cache']);

        // Add filters only if setting parameter "flashpay_orders_transaction_info" is on
        if (ep_is_enabled(Ep_Gateway_Settings_General::OPTION_TRANSACTION_INFO)) {
            // For legacy order storage
            add_filter('manage_edit-shop_order_columns', [$this, 'filter_shop_order_posts_columns'], 10, 1);
            add_filter('manage_shop_order_posts_custom_column', [$this, 'apply_custom_order_data']);
            add_filter('manage_shop_subscription_posts_custom_column', [$this, 'apply_custom_order_data'], 10, 2);

            // For High-Performance Order Storage feature
            add_filter('manage_woocommerce_page_wc-orders_columns', [$this, 'add_column_headers_to_order_list'], 999, 1);
            add_action('manage_woocommerce_page_wc-orders_custom_column', [$this, 'add_column_contents_to_order_list'], 999, 2);
        }
    }

    /**
     * <h2>Adds a new "Payment" column to "Orders" list.</h2>
     *
     * @param array $columns
     * @since  2.0.0
     * @return array
     */
    public function add_column_headers_to_order_list($columns)
    {
        $reordered_columns = [];

        // Inserting columns to a specific location
        foreach ($columns as $key => $column) {
            $reordered_columns[$key] = $column;

            if ($key === 'order_status') {
                // Inserting after "Status" column
                $reordered_columns['flashpay_payment_info'] = __('Payment', 'woo-flashpay');
            }
        }
        return $reordered_columns;
    }

    /**
     * <h2>Applies payment state to the order data overview.</h2>
     *
     * @since  2.0.0
     * @return void
     */
    public function add_column_contents_to_order_list($column, $order = false)
    {
        if (!$order) {
            [$order, $type] = $this->get_order_with_type();
        } else {
            [$order, $type] = ep_get_order($order->ID, true);
        }

        if (!$order) {
            return;
        }

        // Show transaction ID on the overview
        if (!in_array($type, ['shop_order', 'shop_subscription'])) {
            return;
        }

        if ($column !== 'flashpay_payment_info') {
            return;
        }

        // Insert transaction id and payment status if any
        $payment_id = $order->get_payment_id();

        if (!$payment_id || !$order->is_ep()) {
            return;
        }

        if ($order->subscription_is_renewal_failure()) {
            $status = Ep_Gateway_Payment_Status::DECLINE_RENEWAL;
        } else {
            $status = $order->get_ep_status();
        }

        ep_get_view('html-order-table-payment-data.php', [
            'payment_status' => $status,
            'transaction_is_test' => $order->get_is_test(),
        ]);
    }

    /**
     * <h2>Adds a new "Payment" column to "Orders" report.</h2>
     *
     * @param array $show_columns
     * @since  2.0.0
     * @return array
     */
    public function filter_shop_order_posts_columns($show_columns)
    {
        $column_name = 'flashpay_payment_info';
        $column_header = __('Payment', 'woo-flashpay');

        return ep_array_insert_after('shipping_address', $show_columns, $column_name, $column_header);
    }

    /**
     * <h2>Applies payment state to the order data overview.</h2>
     *
     * @since  2.0.0
     * @return void
     */
    public function apply_custom_order_data($column, $order = false)
    {
        if (!$order) {
            [$order, $type] = $this->get_order_with_type();
        } else {
            [$order, $type] = ep_get_order($order->ID, true);
        }

        if (!$order) {
            return;
        }

        // Show transaction ID on the overview
        if (!in_array($type, ['shop_order', 'shop_subscription'])) {
            return;
        }

        if ($column !== 'flashpay_payment_info') {
            return;
        }

        // Insert transaction id and payment status if any
        $payment_id = $order->get_payment_id();

        if (!$payment_id || !$order->is_ep()) {
            return;
        }

        if ($order->subscription_is_renewal_failure()) {
            $status = Ep_Gateway_Payment_Status::DECLINE_RENEWAL;
        } else {
            $status = $order->get_ep_status();
        }

        ep_get_view('html-order-table-payment-data.php', [
            'payment_status' => $status,
            'transaction_is_test' => $order->get_is_test(),
        ]);
    }

    /**
     * <h2>Adds the action meta box inside the single order view.</h2>
     *
     * @since  2.0.0
     * @return void
     */
    public function add_meta_boxes()
    {
        [$order, $type] = $this->get_order_with_type();

        if (!$order) {
            return;
        }

        $allowed_order_types = [
            'shop_order',
            'shop_subscription',
        ];

        if (!in_array($type, $allowed_order_types, true) || !$order->is_ep()) {
            return;
        }

        add_meta_box(
            'flashpay-payment-info',
            __('FLASHPAY Payment', 'woo-flashpay'),
            [$this, 'meta_box_payment_info'],
            ['shop_order', wc_get_page_screen_id('shop_order')],
            'side',
            'high'
        );
        add_meta_box(
            'flashpay-payment-actions',
            __('FLASHPAY Subscription', 'woo-flashpay'),
            [$this, 'meta_box_subscription'],
            ['shop_subscription', wc_get_page_screen_id('shop_subscription')],
            'side',
            'high'
        );
    }

    /**
     * <h2>Inserts the content of the API actions meta box - Payments</h2>
     *
     * @since  2.0.0
     * @return void
     */
    public function meta_box_payment_info()
    {
        [$order, $type] = $this->get_order_with_type();

        if (!$order) {
            return;
        }

        $payment_id = $order->get_payment_id();

        if (!$payment_id || !$order->is_ep()) {
            return;
        }

        do_action('woocommerce_flashpay_meta_box_payment_info_before_content', $order);

        try {
            $payment = $order->get_payment();
            $codeByMapping = Ep_Gateway_Payment_Methods::get_code($order->get_payment_system());
            $ps = empty ($codeByMapping) ? $order->get_payment_system() : $codeByMapping;
            /** @var ?Ep_Gateway_Info_Sum $sum */
            $amount = $payment->get_info()->try_get_sum($sum)
                ? $sum->get_formatted()
                : '';

            ep_get_view(
                'html-meta-box-payment-info.php',
                [
                    'status' => $order->get_ep_status(),
                    'status_name' => ep_get_payment_status_name($order->get_ep_status()),
                    'operation_type' => ep_get_operation_type_name($payment->get_current_type()),
                    'operation_code' => $payment->get_code(),
                    'operation_message' => $payment->get_message(),
                    'payment_method' => $ps,
                    'payment_id' => $payment_id,
                    'logo' => get_ep_payment_method_icon($ps),
                    'amount' => $amount,
                    'is_test' => $order->get_is_test(),
                ]
            );
        } catch (Exception $e) {
            $this->write_meta_box_error($e);
            ep_get_view('html-meta-box-error.php');
        }

        do_action('woocommerce_flashpay_meta_box_payment_info_after_content', $order);
    }

    /**
     * <h2>Inserts the content of the API actions meta box - Subscriptions.</h2>
     *
     * @since  2.0.0
     * @return void
     */
    public function meta_box_subscription()
    {
        [$order, $type] = $this->get_order_with_type();

        if (!$order) {
            return;
        }

        if (get_class($order) !== 'Ep_Gateway_Subscription') {
            return;
        }

        if (!$order->is_ep()) {
            ep_get_log()->debug(__('Subscription not in FLASHPAY.', 'woo-flashpay'));
            return;
        }

        $recurring_id = $order->get_recurring_id();
        $parent = $order->get_order();

        if (!$parent instanceof Ep_Gateway_Order) {
            return;
        }

        try {

            do_action('woocommerce_flashpay_meta_box_subscription_before_content', $order);

            ep_get_view(
                'html-meta-box-subscription.php',
                [
                    'status' => $order->get_status(),
                    'recurring_id' => $recurring_id,
                    'logo' => get_ep_payment_method_icon($parent->get_payment_system()),
                    'is_test' => $order->get_is_test(),
                ]
            );
        } catch (Exception $e) {
            $this->write_meta_box_error($e);
            ep_get_view('html-meta-box-error.php');
        }

        do_action('woocommerce_flashpay_meta_box_subscription_after_content', $order);
    }

    private function write_meta_box_error(Exception $e)
    {
        ep_get_log()->emergency(__('Exception:', 'woo-flashpay'), $e->getMessage());
        ep_get_log()->error(__('Code:', 'woo-flashpay'), $e->getCode());
        ep_get_log()->error(__('File:', 'woo-flashpay'), $e->getFile());
        ep_get_log()->error(__('Line:', 'woo-flashpay'), $e->getLine());
        ep_get_log()->debug($e->getTraceAsString());
    }

    /**
     * @return void
     * @since  2.0.0
     */
    public function enqueue_javascript_backend()
    {
        if ($this->maybe_enqueue_admin_statics()) {
            wp_enqueue_script(
                'flashpay-backend',
                ep_js_url('backend.js'),
                ['jquery'],
                ep_version()
            );

            wp_localize_script(
                'flashpay-backend',
                'ajax_object',
                ['ajax_url' => admin_url('admin-ajax.php')]
            );
        }

        wp_enqueue_script(
            'flashpay-backend-notices',
            ep_js_url('backend-notices.js'),
            ['jquery'],
            ep_version()
        );

        wp_localize_script(
            'flashpay-backend-notices',
            'wcEpBackendNotices',
            ['flush' => admin_url('admin-ajax.php?action=woocommerce_flashpay_flush_runtime_errors')]
        );
    }

    /**
     * <h2>Ajax's method taking manual transaction requests from wp-admin.</h2>
     *
     * @since  2.0.0
     * @return void
     */
    public function ajax_manual_request_actions()
    {
        $param_action = wc_get_var($_REQUEST['flashpay_action']);
        $param_post = wc_get_var($_REQUEST['post']);

        if ($param_action === null || $param_post === null) {
            return;
        }

        if (!woocommerce_flashpay_can_user_manage_payments($param_action)) {
            printf('Your user is not capable of %s payments.', $param_action);
            exit;
        }

        $order = new Ep_Gateway_Order((int) $param_post);

        switch ($param_action) {
            case 'refresh':
                $order->get_payment(true, true);
                break;
            default:
                $this->ajax_action($order, $param_action);
        }
    }

    /**
     * Ajax's method to empty the debug logs
     *
     * @since  2.0.0
     * @return void
     */
    public function ajax_clear_log()
    {
        if (woocommerce_flashpay_can_user_empty_logs()) {
            ep_get_log()->clear();
            echo json_encode([
                'status' => 'success',
                'message' => 'Logs successfully emptied'
            ]);
            exit();
        }
    }

    /**
     * Ajax's method to empty the debug logs
     *
     * @since  2.0.0
     * @return void
     */
    public function ajax_flush_payment_cache()
    {
        global $wpdb;
        if (woocommerce_flashpay_can_user_flush_cache()) {
            $query = 'DELETE FROM ' . $wpdb->options . ' WHERE option_name LIKE \'_transient_wcqp_transaction_%\' OR option_name LIKE \'_transient_timeout_wcqp_transaction_%\';';

            $wpdb->query($query);
            echo json_encode([
                'status' => 'success',
                'message' => 'The transaction cache has been cleared.'
            ]);
            exit();
        }
    }

    /**
     * @since  2.0.0
     * @return bool
     */
    private function maybe_enqueue_admin_statics()
    {
        [$order, $type] = $this->get_order_with_type();

        /**
         * Enqueue on the shop order page
         */
        if ($order && in_array($type, ['shop_order', 'shop_subscription'])) {
            return true;
        }

        return false;
    }

    /**
     * @param Ep_Gateway_Order $order
     * @param string $param_action
     * @since  2.0.0
     * @return void
     */
    private function ajax_action($order, $param_action)
    {
        $transaction_id = $order->get_payment_id();

        try {
            $transaction_info = $order->get_payment();
            $api = new Ep_Gateway_API_Payment();

            // Based on the current transaction state, we check if the requested action is allowed
            if (!$order->is_action_allowed($param_action)) {
                // The action was not allowed.
                throw new Ep_Gateway_API_Exception(
                    sprintf(
                        'Action: "%s", is not allowed for order #%d, with type state "%s"',
                        $param_action,
                        $order->get_id(),
                        $transaction_info->get_current_type()
                    )
                );
            }

            // Check if the action method is available in the payment class
            if (!method_exists($api, $param_action)) {
                throw new Ep_Gateway_API_Exception(
                    sprintf(
                        'Unsupported action: "%s".',
                        $param_action
                    )
                );
            }

            $payment_amount = wc_get_var($_REQUEST['$payment_amount']);

            // Fetch amount if sent.
            $amount = $payment_amount !== null
                ? ep_price_custom_to_multiplied(
                    $payment_amount,
                    $transaction_info->get_currency()
                )
                : $transaction_info->get_remaining_balance();

            // Call the action method and parse the transaction id and order object
            $api->$param_action(
                $transaction_id,
                $order,
                ep_price_multiplied_to_float($amount, $transaction_info->get_currency())
            );
        } catch (Ep_Gateway_API_Exception $e) {
            echo $e->getMessage();
            $e->write_to_logs();
            exit;
        }
    }

    /**
     * Returns the order and post objects
     * Supports High-Performance Order Storage feature
     * 
     * @return array
     */
    private function get_order_with_type()
    {
        global $post;

        if (is_null($post)) {
            if (!isset ($_GET['id'])) {
                return [null, null];
            }

            [$order, $type] = ep_get_order($_GET['id'], true);

            if (!$order) {
                return [null, null];
            }
        } else {
            [$order, $type] = ep_get_order($post->ID, true);
        }

        return [$order, $type];
    }
}