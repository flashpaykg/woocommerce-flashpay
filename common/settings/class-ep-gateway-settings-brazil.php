<?php

defined('ABSPATH') || exit;

/**
 * Ep_Gateway_Settings_Brazil_Online_Banks class
 *
 * @class    Ep_Gateway_Settings_Brazil_Online_Banks
 * @version  3.3.0
 * @package  Ep_Gateway/Settings
 * @category Class
 * @internal
 */
class Ep_Gateway_Settings_Brazil_Online_Banks extends Ep_Gateway_Settings
{
    // region Constants

    /**
     * Internal identifier
     */
    const ID = 'flashpay-brazil';

    /**
     * Shop section identifier
     */
    const BRAZIL_SETTINGS = 'brazil_settings';

    // endregion

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->id = self::ID;
        $this->label = _x('Brazil', 'Settings page', 'woo-flashpay');
        $this->icon = 'online-brazil-banks.svg';

        parent::__construct();

        add_filter('ep_get_settings_' . $this->id, [$this, 'get_settings_brazil_methods']);
    }

    /**
     * Returns the Payment Page fields settings as array.
     *
     * @return array
     */
    public function get_settings_brazil_methods()
    {
        $settings = [
            [
                self::FIELD_ID => self::BRAZIL_SETTINGS,
                self::FIELD_TITLE => _x('Brazil settings', 'Settings section', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_START,
                self::FIELD_DESC => '',
            ],
            [
                self::FIELD_ID => self::OPTION_ENABLED,
                self::FIELD_TITLE => _x('Enable/Disable', 'Settings brazil payments', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_CHECKBOX,
                self::FIELD_DESC => _x('Enable', 'Settings brazil payments', 'woo-flashpay'),
                self::FIELD_TIP => _x(
                    'Before enabling the payment method please contact support@flashpay.kg.',
                    'Settings brazil payments',
                    'woo-flashpay'
                ),
                self::FIELD_DEFAULT => self::NO
            ],
            [
                self::FIELD_ID => self::OPTION_TITLE,
                self::FIELD_TITLE => _x('Title', 'Settings brazil payments', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_TEXT,
                self::FIELD_TIP => _x(
                    'This controls the tittle which the user sees during checkout.',
                    'Settings brazil payments',
                    'woo-flashpay'
                ),
                self::FIELD_DEFAULT => _x('Brazil Online Banking', 'Settings brazil payments', 'woo-flashpay'),
            ],
            [
                self::FIELD_ID => self::OPTION_SHOW_DESCRIPTION,
                self::FIELD_TITLE => _x('Show Description', 'Settings brazil payments', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_CHECKBOX,
                self::FIELD_DESC => _x(
                    'Display the payment method description which user sees during checkout.',
                    'Settings brazil payments',
                    'woo-flashpay'
                ),
                self::FIELD_DEFAULT => self::YES,
            ],
            [
                self::FIELD_ID => self::OPTION_DESCRIPTION,
                self::FIELD_TITLE => _x('Description', 'Settings brazil payments', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_AREA,
                self::FIELD_TIP => _x(
                    'This controls the description which the user sees during checkout.',
                    'Settings brazil payments',
                    'woo-flashpay'
                ),
                self::FIELD_DEFAULT => _x(
                    'You will be redirected to securely continue your payment.',
                    'Settings brazil payments',
                    'woo-flashpay'
                ),
            ],
            [
                self::FIELD_ID => self::OPTION_CHECKOUT_BUTTON_TEXT,
                self::FIELD_TITLE => _x('Order button text', 'Settings brazil payments', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_TEXT,
                self::FIELD_TIP => _x(
                    'Text shown on the submit button when choosing payment method.',
                    'Settings brazil payments',
                    'woo-flashpay'
                ),
                self::FIELD_DEFAULT => _x('Go to payment', 'Settings brazil payments', 'woo-flashpay'),
            ],
            [
                self::FIELD_ID => self::BRAZIL_SETTINGS,
                self::FIELD_TYPE => self::TYPE_END,
            ],
        ];

        return apply_filters('ep_' . $this->id . '_settings', $settings);
    }
}