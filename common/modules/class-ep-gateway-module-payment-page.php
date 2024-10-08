<?php

defined('ABSPATH') || exit;

/**
 * <h2>Request generator to open FLASHPAY Payment Page.</h2>
 *
 * @class    Ep_Gateway_Module_Payment_Page
 * @version  2.0.0
 * @package  Ep_Gateway/Modules
 * @category Class
 */
class Ep_Gateway_Module_Payment_Page extends Ep_Gateway_Registry
{
    // region Constants

    /**
     * <h2>FLASHPAY Payment Page URL protocol.</h2>
     *
     * @const
     * @var string
     * @since 2.0.0
     */
    const PROTOCOL = 'https';

    /**
     * <h2>FLASHPAY Payment Page URL host name.</h2>
     *
     * @const
     * @var string
     * @since 2.0.0
     */
    const HOST = 'paymentpage.flashpay.kg';

    // endregion

    // region Properties

    /**
     * <h2>Stores line items to send to FLASHPAY.</h2>
     *
     * @var array
     * @since 2.0.0
     */
    protected $line_items = [];

    /**
     * <h2>Endpoint for FLASHPAY Payment Page.</h2>
     *
     * @var string
     * @since 2.0.0
     */
    protected $endpoint;

    // endregion

    /**
     * @inheritDoc
     * @since 2.0.0
     * @return void
     */
    protected function init()
    {
        $this->endpoint = sprintf('%s://%s', $this->get_protocol(), $this->get_host());

        // register hooks for AJAX requests
        add_action('wp_ajax_flashpay_process', [$this, 'ajax_process']); // Authorised user
        add_action('wp_ajax_flashpay_break', [$this, 'ajax_process']); // Authorised user
        add_action('wp_ajax_nopriv_flashpay_process', [$this, 'ajax_process']); // Non-authorised user: Guest access
        add_action('wp_ajax_nopriv_flashpay_break', [$this, 'ajax_process']); // Non-authorised user: Guest access
        add_action('wp_ajax_get_data_for_payment_form', [$this, 'ajax_process']); // Authorised user
        add_action('wp_ajax_nopriv_get_data_for_payment_form', [$this, 'ajax_process']); // Non-authorised user: Guest access
        add_action('wp_ajax_get_payment_status', [$this, 'ajax_process']); // Authorised user
        add_action('wp_ajax_nopriv_get_payment_status', [$this, 'ajax_process']); // Non-authorised user: Guest access
        add_action('wp_ajax_check_cart_amount', [$this, 'ajax_process']); // Authorised user
        add_action('wp_ajax_nopriv_check_cart_amount', [$this, 'ajax_process']); // Non-authorised user: Guest access
        add_action('wp_ajax_add_payment_id_to_order', [$this, 'ajax_process']); // Authorised user
        add_action('wp_ajax_nopriv_add_payment_id_to_order', [$this, 'ajax_process']); // Non-authorised user: Guest access

        // register hooks for display payment form on checkout page
        add_action('woocommerce_before_checkout_form', [$this, 'include_frontend_scripts']);

        // register hooks for display payment form on payment page
        add_action('before_woocommerce_pay', [$this, 'include_frontend_scripts']);

        // register hooks for display payment form on block-based checkout page
        add_action('woocommerce_blocks_enqueue_checkout_block_scripts_before', [$this, 'include_new_checkout_scripts']);
        add_action('enqueue_block_editor_assets', [$this, 'include_new_checkout_scripts']);

        // register hooks for additional container on checkout pages
        add_filter('the_content', [$this, 'append_iframe_container'], 10, 1);

        add_action('wp_head', [$this, 'wc_custom_redirect_after_purchase']);
    }

    /**
     * <h2>Returns the FLASHPAY Payment page URL.</h2>
     *
     * @since 2.0.0
     * @return string <p>Payment Page URL.</p>
     */
    public function get_url()
    {
        return $this->endpoint;
    }

    /**
     * <h2>Return payment page options for AJAX request.</h2>
     *
     * @since 2.0.0
     * @throws Exception
     */
    public function ajax_process()
    {
        switch (wc_get_var($_REQUEST['action'])) {
            case 'flashpay_process':
                if (wc_get_var($_REQUEST['woocommerce-process-checkout-nonce']) !== null) {
                    ep_get_log()->debug(__('Flashpay checkout process', 'woo-flashpay'));
                    // Checkout page
                    WC()->checkout()->process_checkout();
                } elseif (wc_get_var($_REQUEST['woocommerce-pay-nonce']) !== null) {
                    // Checkout pay page
                    global $wp;
                    $wp->set_query_var('order-pay', wc_get_var($_REQUEST['order_id'], 0));
                    $_POST['payment_method'] = wc_get_post_data_by_key('payment_method', null);
                    Ep_Gateway_Form_Handler::pay_action();
                }
                break;
            case 'flashpay_break':
                ep_get_log()->debug(__('Flashpay break process', 'woo-flashpay'));
                $order_id = intval(wc_get_post_data_by_key('order_id', 0));

                if ($order_id > 0) {
                    $order = ep_get_order($order_id);

                    $result = [
                        'redirect' => $order->get_checkout_payment_url(),
                    ];
                    wp_send_json($result);
                }
                break;
            case 'get_data_for_payment_form':
                $this->get_data_for_payment_form();
                break;
            case 'get_payment_status':
                $this->get_payment_status();
                break;
            case 'check_cart_amount':
                $this->check_cart_amount(wc_get_var($_REQUEST['amount'], '0'));
                break;
        }
    }

    /**
     * <h2>Injects scripts and styles into the site.</h2>
     *
     * @since 2.0.0
     * @return void
     */
    public function include_frontend_scripts()
    {
        global $wp;

        try {
            if (isset ($wp->query_vars['order-pay']) && absint($wp->query_vars['order-pay']) > 0) {
                $order_id = absint($wp->query_vars['order-pay']); // The order ID
            } else {
                $order_id = is_wc_endpoint_url('order-pay');
            }
        } catch (Exception $e) {
            $order_id = 0;
        }

        $url = ep_payment_page()->get_url();

        // Flashpay merchant bundle.
        wp_enqueue_script(
            'flashpay_merchant_js',
            sprintf('%s/shared/merchant.js', $url),
            [],
            null
        );
        wp_enqueue_style(
            'flashpay_merchant_css',
            sprintf('%s/shared/merchant.css', $url),
            [],
            null
        );

        // Woocommerce Flashpay Plugin frontend
        wp_enqueue_script(
            'flashpay_checkout_script',
            ep_js_url('checkout.js'),
            ['jquery'],
            ep_version()
        );
        wp_enqueue_script(
            'flashpay_frontend_helpers_script',
            ep_js_url('frontend-helpers.js'),
            ['jquery'],
            ep_version()
        );

        wp_localize_script(
            'flashpay_checkout_script',
            'EP',
            [
                'ajax_url' => admin_url("admin-ajax.php"),
                'origin_url' => $url,
                'order_id' => $order_id,
            ]
        );

        wp_enqueue_style('flashpay_loader_css', ep_css_url('loader.css'));
    }

    public function include_new_checkout_scripts()
    {
        global $wp;

        try {
            if (isset ($wp->query_vars['order-pay']) && absint($wp->query_vars['order-pay']) > 0) {
                $order_id = absint($wp->query_vars['order-pay']); // The order ID
            } else {
                $order_id = is_wc_endpoint_url('order-pay');
            }
        } catch (Exception $e) {
            $order_id = 0;
        }

        $url = ep_payment_page()->get_url();

        // Flashpay merchant bundle.
        wp_enqueue_script(
            'flashpay_merchant_js',
            sprintf('%s/shared/merchant.js', $url),
            [],
            null
        );
        wp_enqueue_style(
            'flashpay_merchant_css',
            sprintf('%s/shared/merchant.css', $url),
            [],
            null
        );

        $script_name = 'wc-flashpay-blocks-integration';
        wp_register_script(
            $script_name,
            plugins_url('build/index.js', EP_PLUGIN_PATH),
            [
                'jquery',
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            ep_version(),
            true
        );
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations($script_name);
        }
        wp_enqueue_script($script_name);

        $gateways = (new WC_Payment_Gateways())->get_available_payment_gateways();
        $filtered_gateways = array_keys(array_filter($gateways, function ($gateway) {
            return strpos($gateway->id, 'flashpay-') === 0 && $gateway->enabled === "yes";
        }));

        wp_localize_script(
            $script_name,
            'EP',
            [
                'ajax_url' => admin_url("admin-ajax.php"),
                'origin_url' => $url,
                'order_id' => $order_id,
                'gateways' => $filtered_gateways,
                'ep_pay_nonce' => wp_create_nonce('woocommerce-process_checkout'),
            ]
        );
    }

    /**
     * <h2></h2>
     *
     * @param string $content
     * @since 2.0.0
     * @return string
     */
    public function append_iframe_container($content)
    {
        if (!is_checkout()) {
            return $content;
        }

        return '<div id="flashpay-loader"><div class="lds-flashpay"><div></div><div></div><div></div></div></div>'
            . '<div id="flashpay-iframe"></div><div id="woocommerce_flashpay_checkout_page">'
            . '<div id="flashpay-overlay-loader" class="blockUI blockOverlay flashpay-loader-overlay" style="display: none;"></div>'
            . $content . "</div>";
    }

    /**
     * <h2>Returns FLASHPAY request form data for an order.</h2>
     *
     * @param Ep_Gateway_Order $order <p>Order object.</p>
     * @since 2.0.0
     * @return array <p>Settings for the FLASHPAY payment page.</p>
     * </p>
     */
    public function get_request_url($order, $gateway)
    {
        return apply_filters('ep_append_signature', $this->get_form_data($order, $gateway));
    }

    /**
     * <h2>Returns form data for FLASHPAY Payment Page.</h2>
     *
     * @param Ep_Gateway_Order $order <p>Order for payment.</p>
     * @param Ep_Gateway $gateway
     * @since 2.0.0
     * @return array <p>Form data.</p>
     */
    private function get_form_data(Ep_Gateway_Order $order, $gateway)
    {
        $return_url = esc_url_raw(add_query_arg('utm_nooverride', '1', $gateway->get_return_url($order)));
        $info = apply_filters('ep_create_payment_info', $order);

        // General options
        $values = apply_filters('ep_create_payment_data', $order);
        $values['baseUrl'] = $this->endpoint;

        // Set payment information
        foreach ($info as $key => $value) {
            $values['payment_' . $key] = $value;
        }

        // Set Payment Page Language
        $values = apply_filters('ep_append_language_code', $values);
        // Set Additional data: Customer and Billing data, Receipt etc
        $values = apply_filters('ep_append_additional_variables', $values, $order);
        // Set merchant success url with additional options
        $values = apply_filters('ep_append_merchant_success_url', $values, $return_url);
        // Set merchant fail url with additional options
        $values = apply_filters('ep_append_merchant_fail_url', $values, $return_url);
        // Set merchant return url with additional options
        $values = apply_filters('ep_append_merchant_return_url', $values, esc_url_raw($order->get_checkout_payment_url()));
        // Set merchant callback url
        $values = apply_filters('ep_append_merchant_callback_url', $values);
        // Set merchant success url with additional options
        $values = apply_filters('ep_append_redirect_url', $values, $return_url);
        // Set arguments by current payment gateway
        $values = apply_filters('ep_append_gateway_arguments_' . $gateway->id, $values, $order);
        // Set environment versions
        $values = apply_filters('ep_append_versions', $values);
        // Set FLASHPAY internal interface type
        $values = apply_filters('ep_append_interface_type', $values, true);

        // Clean arguments and return
        return apply_filters('ep_payment_page_clean_parameters', $values);
    }

    /**
     * <h2>Returns the FLASHPAY Payment Page protocol name.</h2>
     *
     * @since 2.0.0
     * @return string <p>FLASHPAY Payment Page protocol name.</b>
     */
    private function get_protocol()
    {
        $proto = getenv('EP_PROTO');

        return is_string($proto) ? $proto : self::PROTOCOL;
    }

    /**
     * <h2>Returns the FLASHPAY Payment Page host name.</h2>
     *
     * @since 2.0.0
     * @return string <p>FLASHPAY Payment Page host name.</p>
     */
    private function get_host()
    {
        $host = getenv('EP_PAYMENTPAGE_HOST');

        return is_string($host) ? $host : self::HOST;
    }

    private function get_data_for_payment_form()
    {
        if (wc_get_var($_GET['pay_for_order'], '') != "" && wc_get_var($_GET['key'], '') != "") {
            $order_key = wc_get_var($_GET['key'], '');
            $order_id = wc_get_order_id_by_order_key($order_key);
            $order = ep_get_order($order_id);
            $payment_currency = $order->get_currency();
            $payment_amount = ep_price_multiply($order->get_total(), $order->get_currency());
            $order->set_payment_system(Ep_Gateway_Operation_Status::AWAITING_CUSTOMER);
        } else {
            $payment_currency = get_woocommerce_currency();
            $payment_amount = ep_price_multiply(WC()->cart->total, $payment_currency);
        }

        $data = [
            'mode' => $payment_amount > 0 ? 'purchase' : 'card_verify',
            'payment_amount' => $payment_amount,
            'payment_currency' => $payment_currency,
            'project_id' => flashpay()->get_project_id(),
            'payment_id' => uniqid('wp_'),
            'force_payment_method' => 'card',
            'target_element' => 'flashpay-iframe-embedded',
            'frame_mode' => 'iframe',
            'merchant_callback_url' => ep_callback_url(),
            'interface_type' => '{"id":18}',
            'payment_methods_options' => "{\"additional_data\":{\"embedded_mode\":true}}",
        ];
        $data = $this->append_recurring_total_form_cart($data);
        if (isset ($order)) {
            $data = apply_filters('ep_append_receipt_data', $data, $order, true);
            $data = apply_filters('ep_append_customer_id', $data, $order);
        } else {
            $data = $this->append_receipt_data_from_cart($data);
            if (WC()->cart->get_customer()->id) {
                $data['customer_id'] = WC()->cart->get_customer()->id;
            }
        }

        $data = apply_filters('ep_append_language_code', $data);

        ep_get_log()->debug(__(json_encode($data), 'woo-flashpay'));

        $data = Ep_Gateway_Signer::get_instance()->sign($data);
        wp_send_json($data);
    }

    private function append_recurring_total_form_cart($data)
    {
        if (class_exists('WC_Subscriptions_Cart') && WC_Subscriptions_Cart::cart_contains_subscription()) {
            $data['recurring'] = '{"register":true,"type":"U"}';
            $data['recurring_register'] = 1;
        }
        return $data;
    }

    private function append_receipt_data_from_cart($data)
    {
        $cart = WC()->cart;
        $totalTax = abs($cart->get_totals()['total_tax']);
        $totalPrice = abs(floatval($cart->get_totals()['total']));
        $receipt = $totalTax > 0
            ? [
                // Item positions.
                'positions' => $this->get_positions($cart),
                // Total tax amount per payment.
                'total_tax_amount' => ep_price_multiply($totalTax, get_woocommerce_currency()),
                'common_tax' => round($totalTax * 100 / ($totalPrice - $totalTax), 2),
            ]
            : [
                // Item positions.
                'positions' => $this->get_positions($cart)
            ];
        $data['receipt_data'] = base64_encode(json_encode($receipt));
        return $data;
    }

    private function get_positions($cart)
    {
        $positions = [];
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $positions[] = $this->get_receipt_position($cart_item, get_woocommerce_currency());
        }
        return $positions;
    }
    private function get_receipt_position($item, $currency)
    {
        $product = $item['data'];
        $quantity = abs($item['quantity']);

        $price = abs((float) $product->get_price() * (float) $item['quantity']);
        $description = esc_attr($product->name);
        $data = [
            // Required. Amount of the positions.
            'amount' => ep_price_multiply($price, $currency),
        ];
        if ($quantity > 0) {
            // Quantity of the goods or services. Multiple of: 0.000001.
            $data['quantity'] = $quantity;
        }
        if (strlen($description) > 0) {
            // Goods or services description. >= 1 characters<= 255 characters.
            $data['description'] = $this->limit_length($description, 255);
        }

        $totalTax = abs($item['line_tax']);

        if ($totalTax > 0) {
            // Tax percentage for the position. Multiple of: 0.01.
            $data['tax'] = round($totalTax * 100 / $price, 2);
            // Tax amount for the position.
            $data['tax_amount'] = ep_price_multiply($totalTax, $currency);
        }
        return $data;
    }

    private function limit_length($string, $limit = 127)
    {
        $str_limit = $limit - 3;

        if (function_exists('mb_strimwidth')) {
            return mb_strlen($string) > $limit
                ? mb_strimwidth($string, 0, $str_limit) . '...'
                : $string;
        }

        return strlen($string) > $limit
            ? substr($string, 0, $str_limit) . '...'
            : $string;
    }

    public function wc_custom_redirect_after_purchase()
    {
        if (!is_wc_endpoint_url('order-received'))
            return;
        global $wp;
        // If order_id is defined
        if (isset ($wp->query_vars['order-received']) && absint($wp->query_vars['order-received']) > 0):
            $order_key = wc_get_var($_GET['key'], '');
            $order_id = wc_get_order_id_by_order_key($order_key);
            $order = ep_get_order($order_id);

            ?>
            <script type="text/javascript">
                // order-receive page status (ty page or failed)
                var order_is_failed = <?= ($order->get_status() == 'failed') ? 'true' : 'false' ?>;
                let result = {};

                function get_status() {
                    jQuery.ajax({
                        type: 'POST',
                        url: '<?= admin_url("admin-ajax.php") ?>' + window.location.search,
                        data: [{ 'name': 'action', 'value': 'get_payment_status' }],
                        dataType: 'json',
                        success: function (response) {
                            result = response;
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            console.log('Error while getting order complete status');
                        }
                    });
                    if (result['callback_received']) {
                        if (!(result['status'] ^ order_is_failed)) {
                            location.reload();
                        } else {
                            loader = jQuery('.blockUI');
                            if (loader.length > 0) {
                                loader[0].remove();
                            }
                            return
                        }
                    } else if ((jQuery('.blockUI').length < 1) && (document.getElementsByTagName('body').length > 0)) {
                        document.getElementsByTagName('body')[0].innerHTML += '<div class="blockUI blockOverlay flashpay-loader-overlay"></div>';
                    }
                    setTimeout(get_status, 400);
                }

                get_status();

            </script>
            <?php
        endif;
    }

    private function get_payment_status()
    {
        $order_key = wc_get_var($_GET['key'], '');
        $order_id = wc_get_order_id_by_order_key($order_key);
        $order = ep_get_order($order_id);
        $status = $order->get_ep_status();
        $statuses = [
            Ep_Gateway_Payment_Status::SUCCESS,
            Ep_Gateway_Payment_Status::DECLINE,
            Ep_Gateway_Payment_Status::EXPIRED,
            Ep_Gateway_Payment_Status::INTERNAL_ERROR,
            Ep_Gateway_Payment_Status::EXTERNAL_ERROR,
            Ep_Gateway_Payment_Status::AWAITING_CONFIRMATION,
            Ep_Gateway_Payment_Status::AWAITING_CUSTOMER
        ];
        $data = [
            'callback_received' => in_array($status, $statuses) ? true : false,
            'status' => in_array($status, [Ep_Gateway_Payment_Status::SUCCESS, Ep_Gateway_Payment_Status::AWAITING_CONFIRMATION]) ? true : false,
        ];
        wp_send_json($data);
    }

    private function check_cart_amount($query_amount)
    {
        $query_amount = (int) $query_amount;
        $cart_amount = ep_price_multiply(WC()->cart->total, get_woocommerce_currency());
        wp_send_json(['amount_is_equal' => ($query_amount === $cart_amount)]);
    }

    private function add_payment_id_to_order()
    {
        $order_id = wc_get_var($_REQUEST['order_id'], 0);
        $payment_id = wc_get_var($_REQUEST['payment_id'], '');
        $meta_id = wc_add_order_item_meta($order_id, 'payment_id', $payment_id, true);
        if ($meta_id) {
            wp_send_json(['status' => 'success']);
        } else {
            wp_send_json(['status' => 'error']);
        }
    }
}
