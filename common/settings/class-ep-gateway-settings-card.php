<?php

defined('ABSPATH') || exit;

/**
 * Ep_Gateway_Settings_Card class
 *
 * @class    Ep_Gateway_Settings_Card
 * @version  3.0.0
 * @package  Ep_Gateway/Settings
 * @category Class
 * @internal
 */
class Ep_Gateway_Settings_Card extends Ep_Gateway_Settings
{
    // region Constants

    /**
     * Internal identifier
     */
    const ID = 'flashpay-card';

    /**
     * Card settings section identifier
     */
    const CARD_SETTINGS = 'card_settings';

    // endregion

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->id = self::ID;
        $this->label = _x('Card settings', 'Settings page', 'woo-flashpay');
        $this->icon = 'card.svg';

        parent::__construct();

        add_filter('ep_get_settings_' . $this->id, [$this, 'get_settings_card']);
    }

    /**
     * Returns the Payment Page fields settings as array.
     *
     * @return array
     */
    public function get_settings_card()
    {
        $settings = [
            [
                self::FIELD_ID => self::CARD_SETTINGS,
                self::FIELD_TITLE => _x('Card settings', 'Settings section', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_START,
                self::FIELD_DESC => '',
            ],
            [
                self::FIELD_ID => self::OPTION_ENABLED,
                self::FIELD_TITLE => _x('Enable/Disable', 'Settings card payments', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_CHECKBOX,
                self::FIELD_DESC => _x('Enable', 'Settings card payments', 'woo-flashpay'),
                self::FIELD_TIP => _x('Before enabling the payment method please contact support@flashpay.kg', 'Settings card payments', 'woo-flashpay'),
                self::FIELD_DEFAULT => self::NO,
            ],
            [
                self::FIELD_ID => self::OPTION_TITLE,
                self::FIELD_TITLE => _x('Title', 'Settings card payments', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_TEXT,
                self::FIELD_TIP => _x(
                    'This controls the tittle which the user sees during checkout.',
                    'Settings card payments',
                    'woo-flashpay'
                ),
                self::FIELD_DEFAULT => _x('Card payments', 'Settings card payments', 'woo-flashpay'),
            ],
            [
                self::FIELD_ID => self::OPTION_SHOW_DESCRIPTION,
                self::FIELD_TITLE => _x('Show Description', 'Settings card payments', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_CHECKBOX,
                self::FIELD_DESC => _x(
                    'Display the payment method description which user sees during checkout.',
                    'Settings card payments',
                    'woo-flashpay'
                ),
                self::FIELD_DEFAULT => self::NO,
            ],
            [
                self::FIELD_ID => self::OPTION_DESCRIPTION,
                self::FIELD_TITLE => _x('Description', 'Settings card payments', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_AREA,
                self::FIELD_TIP => _x(
                    'This controls the description which the user sees during checkout',
                    'Settings card payments',
                    'woo-flashpay'
                ),
                self::FIELD_DEFAULT => _x(
                    '',
                    'Settings card payments',
                    'woo-flashpay'
                ),
            ],
            [
                self::FIELD_ID => self::OPTION_CHECKOUT_BUTTON_TEXT,
                self::FIELD_TITLE => _x('Order button text', 'Settings card payments', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_TEXT,
                self::FIELD_TIP => _x(
                    'Text shown on the submit button when choosing payment method.',
                    'Settings card payments',
                    'woo-flashpay'
                ),
                self::FIELD_DEFAULT => _x('Go to payment', 'Settings card payments', 'woo-flashpay'),
            ],
            [
                self::FIELD_ID => self::OPTION_MODE,
                self::FIELD_TITLE => _x('Display mode', 'Settings card payments', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_DROPDOWN,
                self::FIELD_TIP => _x(
                    'Payment page display mode',
                    'Settings card payments',
                    'woo-flashpay'
                ),
                self::FIELD_OPTIONS => [
                    self::MODE_REDIRECT => _x('Redirect', 'Display mode', 'woo-flashpay'),
                    self::MODE_POPUP => _x('Popup', 'Display mode', 'woo-flashpay'),
                        //                    self::MODE_IFRAME => _x('iFrame', 'Display mode', 'woo-flashpay'),
                    self::MODE_EMBEDDED => _x('Embedded', 'Display mode', 'woo-flashpay'),
                ],
                self::FIELD_DEFAULT => self::MODE_EMBEDDED,
            ],
            [
                self::FIELD_ID => self::OPTION_POPUP_MISS_CLICK,
                self::FIELD_TITLE => _x('Close on miss click', 'Settings card payments', 'woo-flashpay'),
                self::FIELD_TYPE => self::TYPE_CHECKBOX,
                self::FIELD_DESC => _x(
                    'Close popup window on mouse miss click',
                    'Settings card payments',
                    'woo-flashpay'
                ),
                self::FIELD_DEFAULT => self::NO
            ],
            [
                self::FIELD_ID => self::CARD_SETTINGS,
                self::FIELD_TYPE => self::TYPE_END,
            ],
        ];

        return apply_filters('ep_' . $this->id . '_settings', $settings);
    }
}