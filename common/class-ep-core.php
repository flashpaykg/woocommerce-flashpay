<?php

final class Ep_Core extends WC_Settings_API
{
    // region Constants
    /**
     * <h2>Test project identifier.</h2>
     *
     * @var int
     * @since 2.0.0
     */
    const TEST_PROJECT_ID = 112;

    /**
     * <h2>Secret key for the test project.</h2>
     *
     * @var string
     * @since 2.0.0
     */
    const TEST_PROJECT_KEY = 'kHRhsQHHhOUHeD+rt4kgH7OZiwE=';

    /**
     * <h2>Global prefix for internal actions.</h2>
     *
     * @var string
     * @since 2.0.0
     */
    const CMS_PREFIX = 'wp_ep';

    /**
     * <h2>Identifier for interface type.</h2>
     *
     * @var int
     * @since 2.0.0
     */
    const INTERFACE_TYPE = 18;

    /**
     * <h2>Plugin version.</h2>
     * <p>Sent into headers for open PP and Gate 2025 API.</p>
     *
     * @var string
     * @since 2.0.0
     */
    const WC_EP_VERSION = '3.4.6';
    // endregion

    /**
     * @var ?Ep_Core
     */
    private static $instance;

    /**
     * @var ?Ep_Form
     */
    private $form;

    /**
     * @var ?Ep_Gateway[]
     */
    private $methods;

    /**
     * @var string
     */
    public $id = 'flashpay';

    private static $classes = [
        Ep_Gateway_Card::class,
        Ep_Gateway_Applepay::class,
        Ep_Gateway_Googlepay::class,
        Ep_Gateway_Banks::class,
        Ep_Gateway_PayPal::class,
        Ep_Gateway_PayPal_PayLater::class,
        Ep_Gateway_Sofort::class,
        Ep_Gateway_Ideal::class,
        Ep_Gateway_Klarna::class,
        Ep_Gateway_Blik::class,
        Ep_Gateway_Giropay::class,
        Ep_Gateway_Brazil_Online_Banks::class,
        Ep_Gateway_More::class,
    ];

    // region Static methods

    /**
     * <h2>Adds action links inside the plugin overview.</h2>
     *
     * @return array <p>Action link list.</p>
     * @since 2.0.0
     */
    public static function add_action_links($links)
    {
        return array_merge([
            '<a href="' . ep_settings_page_url() . '">' . __('Settings', 'woo-flashpay') . '</a>',
        ], $links);
    }
    // endregion

    public static function get_instance()
    {
        if (!self::$instance) {
            self::$instance = new Ep_Core();
        }

        return self::$instance;
    }

    /**
     * <h2>Returns the FLASHPAY external interface type.</h2>
     *
     * @return array
     * @since 1.0.0
     */
    public function get_interface_type()
    {
        return [
            'id' => self::INTERFACE_TYPE,
        ];
    }

    /**
     * <h2>Applies plugin hooks and filters.</h2>
     *
     * @return void
     * @since 2.0.0
     */
    public function hooks()
    {
        Ep_Gateway_Module_Admin_UI::get_instance();
        Ep_Gateway_Module_Payment_Page::get_instance();
        Ep_Gateway_Module_Refund::get_instance();
        Ep_Gateway_API_Protocol::get_instance();

        $this->methods = [
            Ep_Gateway_Settings_Card::ID => Ep_Gateway_Card::get_instance(),
            Ep_Gateway_Settings_PayPal::ID => Ep_Gateway_PayPal::get_instance(),
            Ep_Gateway_Settings_PayPal_PayLater::ID => Ep_Gateway_PayPal_PayLater::get_instance(),
            Ep_Gateway_Settings_Klarna::ID => Ep_Gateway_Klarna::get_instance(),
            Ep_Gateway_Settings_Sofort::ID => Ep_Gateway_Sofort::get_instance(),
            Ep_Gateway_Settings_Blik::ID => Ep_Gateway_Blik::get_instance(),
            Ep_Gateway_Settings_Ideal::ID => Ep_Gateway_Ideal::get_instance(),
            Ep_Gateway_Settings_Banks::ID => Ep_Gateway_Banks::get_instance(),
            Ep_Gateway_Settings_Giropay::ID => Ep_Gateway_Giropay::get_instance(),
            Ep_Gateway_Settings_Brazil_Online_Banks::ID => Ep_Gateway_Brazil_Online_Banks::get_instance(),
            Ep_Gateway_Settings_Googlepay::ID => Ep_Gateway_Googlepay::get_instance(),
            Ep_Gateway_Settings_Applepay::ID => Ep_Gateway_Applepay::get_instance(),
            Ep_Gateway_Settings_More::ID => Ep_Gateway_More::get_instance(),
        ];

        if (ep_subscription_is_active()) {
            WC_Gateway_Flashpay_Module_Subscription::get_instance();
        }

        add_action('woocommerce_api_wc_' . $this->id, [Ep_Gateway_Callbacks::class, 'handle']);

        $this->installation_hooks();

        add_filter(
            'plugin_action_links_plugin-flashpay/gateway-flashpay.php',
            [$this, 'add_action_links']
        );
    }


    /**
     * <h2>Returns the merchant project identifier.</h2>
     *
     * @return int
     * @since 3.0.0
     */
    public function get_project_id()
    {
        return ep_is_enabled(Ep_Gateway_Settings_General::OPTION_TEST)
            ? self::TEST_PROJECT_ID
            : (int) flashpay()->get_general_option(Ep_Gateway_Settings_General::OPTION_PROJECT_ID);
    }

    /**
     * <h2>Output the admin options table.</h2>
     * <p>Overrides the base function and renders an HTML-page.</p>
     *
     * @override
     * @return void
     * @since 2.0.0
     */
    public function admin_options()
    {
        echo '<img src="' . ep_img_url('flashpay.svg') . '" alt="" class="ep_logo right">';
        echo '<h2>FLASHPAY';
        wc_back_link(__('Return to payments', 'woocommerce'), admin_url('admin.php?page=wc-settings&tab=checkout'));
        echo '</h2>';
        $this->settings()->output($this->id);
    }

    /**
     * <h2>Show plugin changes. Code adapted from W3 Total Cache.</h2>
     *
     * @return void
     * @since 3.0.0
     */
    public function in_plugin_update_message($args)
    {
        $upgrade_notice = '';
        echo wp_kses_post($upgrade_notice);
    }

    public function settings()
    {
        if (empty ($this->form)) {
            $this->form = Ep_Form::get_instance();
        }

        return $this->form;
    }

    public function get_payment_methods()
    {
        if (empty ($this->methods)) {
            foreach (self::$classes as $className) {
                $this->methods[] = new $className();
            }
        }

        return $this->methods;
    }

    public function get_payment_classnames()
    {
        return self::$classes;
    }

    public function get_option($key, $empty_value = [])
    {
        if (empty ($this->settings)) {
            $this->init_settings();
        }

        // If there are no settings defined, use defaults.
        if (!is_array($this->settings)) {
            $this->settings = $this->settings()->get_default_settings();
        }

        return array_key_exists($key, $this->settings)
            ? $this->settings[$key]
            : $empty_value;
    }

    /**
     * @inheritDoc
     * @override
     * @return bool
     * @since 3.0.0
     */
    public function update_option($key, $value = '')
    {
        if (empty ($this->settings)) {
            $this->init_settings();
        }

        $this->settings[$key] = $value;

        return update_option(
            $this->get_option_key(),
            apply_filters('woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings),
            'yes'
        );
    }

    public function update_pm_option($payment_method, $key, $value = '')
    {
        $settings = $this->get_option($payment_method);
        $settings[$key] = $value;
        return $this->update_option($payment_method, $settings);
    }

    public function get_pm_option($payment_method, $key, $default = null)
    {
        $settings = $this->get_option($payment_method);

        // Get option default if unset.
        if (!isset ($settings[$key])) {
            $form_fields = $this->get_form_fields();
            $settings[$key] = isset ($form_fields[$key]) ? $this->get_field_default($form_fields[$key]) : '';
        }

        return !is_null($default) && '' === $settings[$key]
            ? $default
            : $settings[$key];
    }

    public function get_general_option($key, $default = null)
    {
        return $this->get_pm_option(Ep_Gateway_Settings_General::ID, $key, $default);
    }

    /**
     * @inheritDoc
     * @override
     * @return string
     * @since 3.0.0
     */
    public function get_option_key()
    {
        return $this->plugin_id . $this->id . '_settings';
    }

    /**
     * <h2>Returns the redeclaration of the class name for the object type.</h2>
     *
     * @param string $classname <p>Base class name.</p>
     * @param string $type <p>Object type.</p>
     * @return string <p>Wrapped or base class name.</p>
     * @since 3.0.0
     */
    public function type_wrapper($classname, $type)
    {
        switch ($type) {
            case 'shop_order':
                return Ep_Gateway_Order::class;
            case 'shop_order_refund':
                return Ep_Gateway_Refund::class;
            case 'shop_subscription':
                return Ep_Gateway_Subscription::class;
            default:
                return $classname;
        }
    }

    // region Private methods

    /**
     * <h2>Parse update notice from readme file.</h2>
     *
     * @param string $content
     * @return string
     * @since 3.0.0
     */
    private function parse_update_notice($content)
    {
        // Output Upgrade Notice
        $matches = null;
        $regexp = '~==\s*Upgrade Notice\s*==\s*=\s*(.*)\s*=(.*)(=\s*'
            . preg_quote(self::WC_EP_VERSION, '/') . '\s*=|$)~Uis';
        $upgrade_notice = '';

        if (preg_match($regexp, $content, $matches)) {
            $version = trim($matches[1]);
            $notices = (array) preg_split('~[\r\n]+~', trim($matches[2]));

            if (version_compare(self::WC_EP_VERSION, $version, '<')) {

                $upgrade_notice .= '<div class="wc_plugin_upgrade_notice">';

                foreach ($notices as $line) {
                    /** @noinspection HtmlUnknownTarget */
                    $upgrade_notice .= wp_kses_post(
                        preg_replace(
                            '~\[([^]]*)]\(([^)]*)\)~',
                            '<a href="${2}">${1}</a>',
                            $line
                        )
                    );
                }

                $upgrade_notice .= '</div> ';
            }
        }

        return wp_kses_post($upgrade_notice);
    }

    /**
     * <h2>Setup plugin installation hooks.</h2>
     *
     * @return void
     * @since 3.0.0
     */
    private function installation_hooks()
    {
        add_action('wp_ajax_flashpay_run_data_upgrader', [Ep_Gateway_Install::get_instance(), 'ajax_run_upgrade']);
        add_action(
            'in_plugin_update_message-woocommerce-flashpay/woocommerce-flashpay.php',
            [$this, 'in_plugin_update_message']
        );
    }

    // endregion
}