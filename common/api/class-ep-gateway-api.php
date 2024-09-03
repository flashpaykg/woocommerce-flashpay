<?php

defined('ABSPATH') || exit;

/**
 * <h2>Base FLASHPAY Gate2025 API</h2>
 *
 * @class    Ep_Gateway_API
 * @version  2.0.0
 * @package  Ep_Gateway/Api
 * @category Class
 */
class Ep_Gateway_API
{
    // region Constants

    /**
     * <h2>Default API protocol name.</h2>
     *
     * @var string
     * @since 2.0.0
     */
    const PROTOCOL = 'https';

    /**
     * <h2>Default API host name.</h2>
     *
     * @var string
     * @since 2.0.0
     */
    const HOST = 'api.flashpay.kg';

    /**
     * <h2>Default API protocol version.</h2>
     *
     * @var string
     * @since 2.0.0
     */
    const VERSION = 'v2';

    // endregion

    // region Properties

    /**
     * <h2>The API url.</h2>
     *
     * @var string
     * @since 2.0.0
     */
    private $api_url;

    /**
     * <h2>Request headers.</h2>
     *
     * @var string[]
     * @since 2.0.0
     */
    private $headers;

    // endregion

    /**
     * <h2>API Constructor.</h2>
     *
     * @param string $append <p>Additional parameters to base API URL.</p>
     * @since 2.0.0
     */
    public function __construct($append = '')
    {
        $this->api_url = sprintf(
            '%s://%s/%s%s',
            $this->getProtocol(),
            $this->getHost(),
            $this->getVersion(),
            $append !== '' ? '/' . $append : ''
        );

        $this->headers = [
            'X-FLASHPAY_PLUGIN' => Ep_Core::WC_EP_VERSION,
            'X-WORDPRESS' => wp_version(),
            'X-WOOCOMMERCE' => wc_version(),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $this->hooks();
    }

    /**
     * <h2>Adds hooks and filters.</h2>
     *
     * @since 2.0.0
     * @return void
     */
    protected function hooks()
    {
    }

    /**
     * <h2>Performs an API GET request.</h2>
     *
     * @param string $path <p>API request string.</p>
     * @since 2.0.0
     * @return array <p>Response data as array.</p>
     */
    final public function get($path)
    {
        // Start the request and return the response
        return $this->execute('GET', $path);
    }

    /**
     * <h2>Performs an API POST request.</h2>
     *
     * @param string $path <p>API request string.</p>
     * @param array $form [optional] <p>Form data for send. Default: blank array.</p>
     * @since 2.0.0
     * @return array <p>Response data as array.</p>
     */
    final public function post($path, $form = [])
    {
        // Start the request and return the response
        return $this->execute('POST', $path, $form);
    }

    /**
     * <h2>Returns form data with general section.</h2>
     *
     * @param array $data <p>Order object or request identifier.</p>
     * @since 3.0.0
     * @return array
     */
    protected function create_general_section($data)
    {
        return [
            Ep_Gateway_Signer::GENERAL => $data
        ];
    }

    protected function get_general_data($order)
    {
        return [
            Ep_Gateway_Signer::GENERAL => apply_filters(
                'ep_append_merchant_callback_url',
                apply_filters('ep_create_general_data', $order)
            )
        ];
    }

    // region Private methods

    /**
     * <h2>Returns the API request string and appends it to the API url.</h2>
     *
     * @param string $params <p>API request string.</p>
     * @since 2.0.0
     * @return string <p>Current object.</p>
     */
    private function get_url($params)
    {
        return $this->api_url . '/' . trim($params, '/');
    }

    /**
     * <h2>Executes the API request.</h2>
     *
     * @param string $request_type <p>The type of request being made.</p>
     * @param string $path <p>API request string.</p>
     * @param array $form [optional] <p>Form data for send. Default: blank array.</p>
     * @since 2.0.0
     * @return array <p>Response data as array.</p>
     */
    private function execute($request_type, $path, $form = [])
    {
        switch ($request_type) {
            case 'GET':
                $response = wp_remote_get($this->get_url($path), $this->get_args($form));
                break;
            case 'HEAD':
                $response = wp_remote_head($this->get_url($path), $this->get_args($form));
                break;
            default:
                $response = wp_remote_post($this->get_url($path), $this->get_args($form));
                break;
        }

        $data = wp_remote_retrieve_body($response);
        $status = intval(wp_remote_retrieve_response_code($response));

        // Log request
        $this->log($request_type, $form, $data, $status, $path);

        $result = $status === 200
            // Parse and return response
            ? json_decode($data, true)
            // Error response
            : [
                Ep_Gateway_Info_Status::FIELD_ERRORS => [
                    [
                        Ep_Gateway_Info_Error::FIELD_MESSAGE => 'Communication error',
                    ]
                ]
            ];

        if ($result !== null && !is_bool($result)) {
            return $result;
        }

        ep_get_log()->warning(
            _x('JSON parse data with error: ', 'Log information', 'woo-flashpay'),
            json_last_error_msg()
        );
        ep_get_log()->info(
            _x('JSON source string data: ', 'Log information', 'woo-flashpay'),
            $data
        );

        return [];
    }

    /**
     * <h2>Returns the FLASHPAY Gate2025 API protocol name.</h2>
     *
     * @since 2.0.0
     * @return string <p>Protocol name.</p>
     */
    private function getProtocol()
    {
        $proto = getenv('EP_PROTO');

        return is_string($proto) ? $proto : self::PROTOCOL;
    }

    /**
     * <h2>Returns the FLASHPAY Gate2025 API host name.</h2>
     *
     * @since 2.0.0
     * @return string <p>Host name.</p>
     */
    private function getHost()
    {
        $host = getenv('EP_GATE_HOST');

        return is_string($host) ? $host : self::HOST;
    }

    /**
     * <h2>Returns the FLASHPAY Gate2025 API version.</h2>
     *
     * @since 2.0.0
     * @return string <p>API version.</b>
     */
    private function getVersion()
    {
        $version = getenv('EP_GATE_VERSION');

        return is_string($version) ? $version : self::VERSION;
    }

    /**
     * <h2>Returns the request properties.</h2>
     *
     * @since 2.2.1
     * @return array <p>Request properties.</b>
     */
    private function get_args(array $body = [])
    {
        $args = [
            'timeout' => '5',
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => $this->headers,
        ];

        if (count($body) > 0) {
            $body = json_encode($body);

            if ($body !== false) {
                $args['body'] = $body;
            } else {
                ep_get_log()->alert(json_last_error_msg());
            }
        }

        return $args;
    }

    /**
     * <h2>Logs result of execution.<h2>
     *
     * @param string $request_type <p>Request type.</p>
     * @param array $request_data <p>Form data as array.</p>
     * @param string $response_data <p>Response raw data.</p>
     * @since 2.0.0
     * @return void
     */
    private function log($request_type, $request_data, $response_data, $response_code, $path)
    {
        ep_get_log()->debug(__('~ START => [API Execution process]', 'woo-flashpay'));
        ep_get_log()->debug(__('Request URL:', 'woo-flashpay'), $this->get_url($path));
        ep_get_log()->debug(__('Request type:', 'woo-flashpay'), $request_type);
        ep_get_log()->debug(__('Form data:', 'woo-flashpay'), json_encode($request_data));
        ep_get_log()->debug(__('Response code:', 'woo-flashpay'), $response_code);
        ep_get_log()->debug(__('Response raw:', 'woo-flashpay'), $response_data);
        ep_get_log()->debug(__('[API Execution process] => END ~', 'woo-flashpay'));
    }

    // endregion
}
