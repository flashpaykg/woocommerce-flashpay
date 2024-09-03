<?php

/**
 * Make the object available for later use
 *
 * @return Ep_Core
 */
function flashpay()
{
    return Ep_Core::get_instance();
}

/**
 * Returns current version for frontend.
 *
 * @return string
 */
function ep_version()
{
    return 'wc_ep-' . Ep_Core::WC_EP_VERSION;
}

if (!function_exists('wp_version')) {
    function wp_version()
    {
        include (ABSPATH . WPINC . '/version.php');
        /** @noinspection PhpUndefinedVariableInspection */
        return $wp_version;
    }
}

if (!function_exists('wc_version')) {
    function wc_version()
    {
        return WC()->version;
    }
}

/**
 * Get the plugin url.
 * @return string
 */
function ep_plugin_url()
{
    return untrailingslashit(plugins_url('/', EP_PLUGIN_PATH));
}

/**
 * Get the plugin path.
 * @return string
 */
function ep_plugin_path()
{
    return untrailingslashit(plugin_dir_path(EP_PLUGIN_PATH));
}

function ep_assets_path($file_name)
{
    return ep_plugin_path() . '/assets/' . trim($file_name, '/');
}

function ep_assets_url($file_name)
{
    return ep_plugin_url() . '/assets/' . trim($file_name, '/');
}

function ep_js_path($file_name)
{
    return ep_assets_path('js/' . trim($file_name, '/'));
}

function ep_css_path($file_name)
{
    return ep_assets_path('css/' . trim($file_name, '/'));
}

function ep_css_url($file_name)
{
    return esc_url(ep_assets_url('css/' . trim($file_name, '/')));
}

function ep_js_url($file_name)
{
    return esc_url(ep_assets_url('js/' . trim($file_name, '/')));
}

function ep_img_url($file_name)
{
    return esc_url(ep_assets_url('img/' . trim($file_name, '/')));
}

/**
 * Returns the link to the gateway settings page.
 *
 * @return string
 */
function ep_settings_page_url($sub = Ep_Gateway_Settings_General::ID)
{
    if ($sub !== Ep_Gateway_Settings_General::ID) {
        return admin_url('admin.php?page=wc-settings&tab=checkout&section=' . esc_attr($sub));
    }

    foreach (ep_payment_methods() as $method) {
        return admin_url('admin.php?page=wc-settings&tab=checkout&section=' . $method->id . '&sub=general');
    }
}

/**
 * @return string
 */
function ep_doc_link()
{
    return 'https://developers.flashpay.kg/en/en_CMS__wordpress.html';
}

/**
 * Returns a link to the manual contains description by error code.
 * @param string $code Error code
 * @return string
 *
 */
function ep_error_code_link($code)
{
    return 'https://developers.flashpay.kg/en/en_Gate__Unified_Codes.html?hl= ' . $code;
}

/**
 * Returns a link to the log files in the WP backend.
 */
function ep_admin_link()
{
    $log_path = wc_get_log_file_path(Ep_Gateway_Log::FLASHPAY_DOMAIN);
    $log_path_parts = explode('/', $log_path);

    return add_query_arg([
        'page' => 'wc-status',
        'tab' => 'logs',
        'log_file' => end($log_path_parts)
    ], admin_url('admin.php'));
}

/**
 * Fetches and shows a view
 *
 * @param string $path
 * @param array $args
 */
function ep_get_view($path, $args = [])
{
    if (is_array($args) && !empty ($args)) {
        extract($args);
    }

    $file = __DIR__ . '/../views/' . trim($path);

    if (file_exists($file)) {
        include $file;
    }
}

/**
 * @return void
 */
function ep_load_i18n()
{
    load_plugin_textdomain(
        'woo-flashpay',
        false,
        dirname(plugin_basename(__FILE__)) . DIRECTORY_SEPARATOR . 'languages'
    );
}

/**
 * Checks if a setting options is enabled by checking on yes/no data.
 *
 * @param string $key
 *
 * @return bool
 */
function ep_is_enabled($key, $payment_method = Ep_Gateway_Settings_General::ID)
{
    return flashpay()
        ->get_pm_option(
            $payment_method,
            $key,
            Ep_Gateway_Settings::NO
        ) === Ep_Gateway_Settings::YES;
}

/**
 * @return Ep_Gateway[]
 * @since 3.0.0
 */
function ep_payment_methods()
{
    return flashpay()->get_payment_methods();
}

/**
 * @return string[]
 * @since 3.0.0
 */
function ep_payment_classnames()
{
    return flashpay()->get_payment_classnames();
}

/**
 * @return bool
 * @since 3.0.0
 */
function ep_has_available_methods()
{
    foreach (ep_payment_methods() as $method) {
        if ($method->enabled) {
            return true;
        }
    }

    return false;
}

/**
 * Inserts a new key/value after the key in the array.
 *
 * @param string $needle The array key to insert the element after
 * @param array $haystack An array to insert the element into
 * @param string $new_key The key to insert
 * @param mixed $new_value An value to insert
 * @return array The new array if the $needle key exists, otherwise an unmodified $haystack
 */
function ep_array_insert_after($needle, $haystack, $new_key, $new_value)
{

    if (array_key_exists($needle, $haystack)) {

        $new_array = [];

        foreach ($haystack as $key => $value) {

            $new_array[$key] = $value;

            if ($key === $needle) {
                $new_array[$new_key] = $new_value;
            }
        }

        return $new_array;
    }

    return $haystack;
}

/**
 * @param string $payment_type
 * @return string
 */
function get_ep_payment_method_icon($payment_type)
{
    $logos = [
        'card' => 'card.svg',
        'alipay' => 'alipay.svg',
        'apple_pay' => 'apple_pay_core.svg',
        'apple_pay_core' => 'apple_pay_core.svg',
        'bigcash' => 'bigcash.svg',
        'crypto' => 'crypto.svg',
        'google-pay' => 'google_pay.png',
        'google-pay-host' => 'google_pay.svg',
        'jeton-wallet' => 'jetonWallet.svg',
        'mobile' => 'mobile.svg',
        'monetix-wallet' => 'monetix-wallet.svg',
        'neteller' => 'neteller.svg',
        'paypal-wallet' => 'paypal-wallet.svg',
        'profee' => 'profee.svg',
        'rapid' => 'rapid.svg',
        'skrill' => 'skrill.svg',
        'sofort' => 'sofort.svg',
        'unionpay' => 'unionpay.svg',
        'webmoney' => 'webmoney.svg',
    ];

    if (array_key_exists(trim($payment_type), $logos)) {
        return ep_img_url($logos[$payment_type]);
    }

    return ep_img_url('flashpay.svg');
}

/**
 * Returns FLASHPAY Logger
 * @return Ep_Gateway_Log
 */
function ep_get_log()
{
    return Ep_Gateway_Log::get_instance();
}

/**
 * Returns FLASHPAY Signer.
 *
 * @return Ep_Gateway_Signer
 */
function ep_get_signer()
{
    return Ep_Gateway_Signer::get_instance();
}

/**
 * <h2>Appends a signature to the data.</h2>
 * @param array &$data <p>The data for signature.</p>
 * @since 2.0.0
 * @return void
 * @throws Ep_Gateway_Signature_Exception <p>
 * When the key or value of one of the parameters contains the character
 * {@see Ep_Gateway_Signer::VALUE_SEPARATOR} symbol.
 * </p>
 */
function ep_sign_request_data(array &$data)
{
    ep_get_signer()->sign($data);
}

/**
 * <h2>Returns the result of data signature verification.</h2>
 * @param array $data <p>Data to verify.</p>
 * @since 2.0.0
 * @return bool <p><b>TRUE</b> if the signature is valid or <b>FALSE</b> otherwise.</p>
 * @throws Ep_Gateway_Signature_Exception <p>
 * When the key or value of one of the parameters contains the character
 * {@see Ep_Gateway_Signer::VALUE_SEPARATOR} symbol.
 * </p>
 */
function ep_check_signature($data)
{
    return ep_get_signer()->check($data);
}

/**
 * @return Ep_Gateway_Module_Payment_Page
 */
function ep_payment_page()
{
    return Ep_Gateway_Module_Payment_Page::get_instance();
}

function ep_region_code($country, $region)
{
    $regions = WC()->countries->get_states($country);
    return array_search($region, $regions);
}

/**
 * Display a description.
 *
 * @param  array $attributes custom HTML attributes as key => value pairs.
 * @return string
 * @since  2.2.2
 *
 */
function ep_custom_attributes($attributes)
{
    $result = '';

    foreach ($attributes as $attribute => $attribute_value) {
        $result .= sprintf(' %s="%s"', esc_attr($attribute), esc_attr($attribute_value));
    }

    return $result;
}
