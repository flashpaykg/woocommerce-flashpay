<?php

defined('ABSPATH') || exit;

/**
 * Ep_Gateway_Settings_Applepay class
 *
 * @class    Ep_Gateway_Settings_Applepay
 * @version  3.0.1
 * @package  Ep_Gateway/Settings
 * @category Class
 * @internal
 */
class Ep_Gateway_Settings_Applepay extends Ep_Gateway_Settings
{
    // region Constants

    /**
     * Internal identifier
     */
    const ID = 'flashpay-apple-pay';

    /**
     * Shop section identifier
     */
    const APPLE_PAY_SETTINGS = 'apple-pay_settings';

    // endregion

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->id = self::ID;
        $this->label = _x('Apple pay', 'Settings page', 'woo-flashpay');
        $this->icon = 'apple_pay_core.svg';

        parent::__construct();

        add_filter('ep_get_settings_' . $this->id, [$this, 'get_settings_apple_pay_methods']);
    }

    /**
     * Returns the Payment Page fields settings as array.
     *
     * @return array
     */
    public function get_settings_apple_pay_methods()
    {
        $settings = [
            [
                self::FIELD_ID => self::APPLE_PAY_SETTINGS,
                self::FIELD_TITLE => _x('ApplePay settings', 'Settings section', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_START,
                self::FIELD_DESC => '',
            ],
            [
                self::FIELD_ID => self::OPTION_ENABLED,
                self::FIELD_TITLE => _x('Enable/Disable', 'Settings ApplePay payments', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_CHECKBOX,
                self::FIELD_DESC => _x('Enable', 'Settings ApplePay payments', 'woo-flashpay'),
                self::FIELD_TIP => _x(
                    'Before enabling the payment method please contact support@flashpay.kg',
                    'Settings ApplePay payments',
                    'woo-flashpay'
                ),
                self::FIELD_DEFAULT => self::NO
            ],
            [
                self::FIELD_ID => self::OPTION_TITLE,
                self::FIELD_TITLE => _x('Title', 'Settings ApplePay payments', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_TEXT,
                self::FIELD_TIP => _x(
                    'This controls the tittle which the user sees during checkout.',
                    'Settings ApplePay payments',
                    'woo-flashpay'
                ),
                self::FIELD_DEFAULT => _x('ApplePay', 'Settings Apple Pay payments', 'woo-flashpay'),
            ],
            [
                self::FIELD_ID => self::OPTION_SHOW_DESCRIPTION,
                self::FIELD_TITLE => _x('Show Description', 'Settings ApplePay payments', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_CHECKBOX,
                self::FIELD_DESC => _x(
                    'Display the payment method description which user sees during checkout.',
                    'Settings ApplePay payments',
                    'woo-flashpay'
                ),
                self::FIELD_DEFAULT => self::NO,
            ],
            [
                self::FIELD_ID => self::OPTION_DESCRIPTION,
                self::FIELD_TITLE => _x('Description', 'Settings ApplePay payments', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_AREA,
                self::FIELD_TIP => _x(
                    'This controls the description which the user sees during checkout.',
                    'Settings ApplePay payments',
                    'woo-flashpay'
                ),
                self::FIELD_DEFAULT => _x(
                    'Pay with ApplePay',
                    'Settings ApplePay payments',
                    'woo-flashpay'
                ),
            ],
            [
                self::FIELD_ID => self::OPTION_CHECKOUT_BUTTON_TEXT,
                self::FIELD_TITLE => _x('Order button text', 'Settings ApplePay payments', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_TEXT,
                self::FIELD_TIP => _x(
                    'Text shown on the submit button when choosing payment method.',
                    'Settings ApplePay payments',
                    'woo-flashpay'
                ),
                self::FIELD_DEFAULT => _x('Go to payment', 'Settings ApplePay payments', 'woo-flashpay'),
            ],
            [
                self::FIELD_ID => self::APPLE_PAY_SETTINGS,
                self::FIELD_TYPE => self::TYPE_END,
            ],
        ];

        return apply_filters('ep_' . $this->id . '_settings', $settings);
    }
}