<?php

defined('ABSPATH') || exit;

/**
 * Ep_Gateway_Settings_Banks class
 *
 * @class    Ep_Gateway_Settings_Banks
 * @version  3.0.0
 * @package  Ep_Gateway/Settings
 * @category Class
 * @internal
 */
class Ep_Gateway_Settings_Banks extends Ep_Gateway_Settings
{
    // region Constants

    /**
     * Internal identifier
     */
    const ID = 'flashpay-banks';

    /**
     * Shop section identifier
     */
    const BANKS_SETTINGS = 'banks_settings';

    // endregion

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->id = self::ID;
        $this->label = _x('Pay by Bank', 'Settings page', 'woo-flashpay');
        $this->icon = 'banks.svg';

        parent::__construct();

        add_filter('ep_get_settings_' . $this->id, [$this, 'get_settings_banks_methods']);
    }

    /**
     * Returns the Payment Page fields settings as array.
     *
     * @return array
     */
    public function get_settings_banks_methods()
    {
        $settings = [
            [
                self::FIELD_ID => self::BANKS_SETTINGS,
                self::FIELD_TITLE => _x('Pay by Bank (Open Banking) settings', 'Settings section', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_START,
                self::FIELD_DESC => '',
            ],
            [
                self::FIELD_ID => self::OPTION_ENABLED,
                self::FIELD_TITLE => _x('Enable/Disable', 'Settings banks payments', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_CHECKBOX,
                self::FIELD_DESC => _x('Enable', 'Settings banks payments', 'woo-flashpay'),
                self::FIELD_TIP => _x(
                    'Before enabling the payment method please contact support@flashpay.kg',
                    'Settings banks payments',
                    'woo-flashpay'
                ),
                self::FIELD_DEFAULT => self::NO
            ],
            [
                self::FIELD_ID => self::OPTION_TITLE,
                self::FIELD_TITLE => _x('Title', 'Settings banks payments', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_TEXT,
                self::FIELD_TIP => _x(
                    'This controls the title which the user sees during checkout.',
                    'Settings banks payments',
                    'woo-flashpay'
                ),
                self::FIELD_DEFAULT => _x('Pay by Bank', 'Settings banks payments', 'woo-flashpay'),
            ],
            [
                self::FIELD_ID => self::OPTION_SHOW_DESCRIPTION,
                self::FIELD_TITLE => _x('Show Description', 'Settings banks payments', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_CHECKBOX,
                self::FIELD_DESC => _x(
                    'Display the payment method description which user sees during checkout.',
                    'Settings banks payments',
                    'woo-flashpay'
                ),
                self::FIELD_DEFAULT => self::YES,
            ],
            [
                self::FIELD_ID => self::OPTION_DESCRIPTION,
                self::FIELD_TITLE => _x('Description', 'Settings banks payments', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_AREA,
                self::FIELD_TIP => _x(
                    'This controls the description which the user sees during checkout.',
                    'Settings banks payments',
                    'woo-flashpay'
                ),
                self::FIELD_DEFAULT => _x(
                    'Pay securely with your local bank.',
                    'Settings banks payments',
                    'woo-flashpay'
                ),
            ],
            [
                self::FIELD_ID => self::OPTION_CHECKOUT_BUTTON_TEXT,
                self::FIELD_TITLE => _x('Order button text', 'Settings banks payments', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_TEXT,
                self::FIELD_TIP => _x(
                    'Text shown on the submit button when choosing payment method.',
                    'Settings banks payments',
                    'woo-flashpay'
                ),
                self::FIELD_DEFAULT => _x('Go to payment', 'Settings banks payments', 'woo-flashpay'),
            ],
            [
                self::FIELD_ID => self::BANKS_SETTINGS,
                self::FIELD_TYPE => self::TYPE_END,
            ],
        ];

        return apply_filters('ep_' . $this->id . '_settings', $settings);
    }
}