<?php
/**
 * Plugin Name:       FLASHPAY Payments
 * Plugin URI:        https://flashpay.kg
 * GitHub Plugin URI:
 * Description:       Easy payment from WooCommerce by different methods in single Payment Page.
 * Version:           3.4.6
 * License:           GPL2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       woo-flashpay
 * Domain Path:       /language/
 * Copyright:         © 2017-2023 Flashpay, London
 *
 * @package Ep_Gateway
 * @author FLASHPAY
 * @copyright © 2017-2023 FLASHPAY, London
 */
defined('ABSPATH') || exit;

if (!defined('EP_PLUGIN_PATH')) {
    define('EP_PLUGIN_PATH', __FILE__);
}

require_once __DIR__ . '/helpers/ep-woo-blocks-support.php';

/**
 * Add plugin action links.
 *
 * Add a link to the settings page on the plugins.php page.
 *
 * @param  array  $links List of existing plugin action links.
 * @return array         List of modified plugin action links.
 */
function gateway_flashpay_action_links($links)
{
    $settings = [
        sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=wc-settings&tab=checkout&section=flashpay-card&sub=general'),
            __('Settings', 'woo-flashpay')
        ),
    ];

    return array_merge($settings, $links);
}
add_action('plugin_action_links_' . plugin_basename(__FILE__), 'gateway_flashpay_action_links');

add_action(
    'plugins_loaded',
    function () {
        // Check available woocommerce classes
        if (!class_exists('WC_Dependencies')) {
            require_once __DIR__ . '/common/class-wc-dependencies.php';
        }

        // Check if WooCommerce is active.
        /** @noinspection PhpMultipleClassDeclarationsInspection */
        if (!WC_Dependencies::woocommerce_active_check()) {
            add_action('admin_notices', function () {
                $class = 'notice notice-error';
                $headline = __('FLASHPAY requires WooCommerce to be active.', 'woo-flashpay');
                $message = __('Go to the plugins page to activate WooCommerce', 'woo-flashpay');
                printf('<div class="%1$s"><h2>%2$s</h2><p>%3$s</p></div>', $class, $headline, $message);
            });
            return;
        }

        require_once __DIR__ . '/common/__autoload.php';

        // Instantiate
        flashpay();

        if (ep_has_available_methods()) {
            flashpay()->hooks();
        }

        // Add the gateway to WooCommerce
        add_filter('woocommerce_payment_gateways', function (array $methods) {
            foreach (ep_payment_classnames() as $class_name) {
                $methods[] = $class_name;
            }
            return $methods;
        });

        // Include wp-admin styles
        add_action(
            'admin_enqueue_scripts',
            function () {
            wp_enqueue_style(
                'woocommerce-flashpay-admin-style',
                ep_css_url('woocommerce-flashpay-admin.css'),
                [],
                ep_version()
            );
        }
        );

        // Include wp-frontend styles
        add_action(
            'wp_enqueue_scripts',
            function () {
            wp_enqueue_style(
                'woocommerce-flashpay-frontend-style',
                ep_css_url('woocommerce-flashpay-frontend.css'),
                [],
                ep_version()
            );
        }
        );
    },
    0
);

/**
 * <h2>Run FLASHPAY Gateway installer.</h2>
 *
 * @param string __FILE__ - The current file
 * @param callable - Do the installer/update logic.
 */
register_activation_hook(__FILE__, function () {
    require_once __DIR__ . '/common/__autoload.php';

    $installer = Ep_Gateway_Install::get_instance();

    // Run the installer on the first install.
    if ($installer->is_first_install()) {
        $installer->install();
    }

    if ($installer->is_update_required()) {
        $installer->update();
    }
});
