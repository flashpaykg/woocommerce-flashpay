<?php

defined('ABSPATH') || exit;

/**
 * Ep_Gateway_Operation_Type class
 *
 * @class    Ep_Gateway_Operation_Type
 * @since    2.0.0
 * @package  Ep_Gateway/Helpers
 * @category Class
 * @internal
 */
class Ep_Gateway_Operation_Type
{
    /**
     * Single-message purchase
     */
    const SALE = 'sale';

    /**
     * Purchase again using previously registered recurring.
     */
    const RECURRING = 'recurring';

    /**
     * Recurring update in payment system.
     */
    const RECURRING_UPDATE = 'recurring update';

    /**
     * Recurring cancel in payment system.
     */
    const RECURRING_CANCEL = 'recurring cancel';

    /**
     * First step of double-message purchase - hold.
     */
    const AUTH = 'auth';

    /**
     * Second step of double-message purchase - confirmation.
     */
    const CAPTURE = 'capture';

    /**
     * Void previously held double-message transaction.
     */
    const CANCEL = 'cancel';

    /**
     * Revert purchase.
     */
    const REVERSAL = 'reversal';

    /**
     * Refund back purchase.
     */
    const REFUND = 'refund';

    /**
     * Revert of the refund operation.
     */
    const REFUND_REVERSE = 'refund reverse';

    /**
     * Operation for manual change transaction status.
     */
    const MANUAL_CHANGE = 'manual change';

    /**
     * Operation for account verification
     */
    const ACCOUNT_VERIFICATION = 'account verification';

    /**
     * Create cash voucher for OrangeData.
     */
    const CREATE_CASH_VOUCHER = 'create_cash_voucher';

    /**
     * Operation of taking commission.
     */
    const COMMISSION = 'commission';

    /**
     * Operation for pre-confirm incremental.
     */
    const INCREMENTAL = 'incremental';

    /**
     * Invoice operation - first part of invoice transaction.
     */
    const INVOICE = 'invoice';

    /**
     * Customer initiated action.
     */
    const CUSTOMER_ACTION = 'customer action';

    /**
     * Payment confirmation operation.
     */
    const PAYMENT_CONFIRMATION = 'payment confirmation';

    /**
     * Capture settlement operation.
     */
    const CAPTURE_SETTLEMENT = 'capture settlement';

    private static $names;
    private static $codes;

    public static function get_status_code($status)
    {
        return array_key_exists($status, self::get_status_codes())
            ? self::get_status_codes()[$status]
            : 'undefined';
    }

    public static function get_status_name($status)
    {
        return array_key_exists($status, self::get_status_names())
            ? self::get_status_names()[$status]
            : 'Undefined';
    }

    public static function get_status_names()
    {
        if (!self::$names) {
            self::$names = self::compile_names();
        }

        return self::$names;
    }

    public static function get_status_codes()
    {
        if (!self::$codes) {
            self::$codes = self::compile_codes();
        }

        return self::$codes;
    }

    private static function compile_names()
    {
        return [
            self::SALE => _x('Sale', 'Operation type', 'woo-flashpay'),
            self::RECURRING => _x('Recurring', 'Operation type', 'woo-flashpay'),
            self::RECURRING_CANCEL => _x('Cancel recurring', 'Operation type', 'woo-flashpay'),
            self::RECURRING_UPDATE => _x('Update recurring', 'Operation type', 'woo-flashpay'),
            self::AUTH => _x('Auth', 'Operation type', 'woo-flashpay'),
            self::CAPTURE => _x('Capture', 'Operation type', 'woo-flashpay'),
            self::CANCEL => _x('Cancel', 'Operation type', 'woo-flashpay'),
            self::REVERSAL => _x('Reversal', 'Operation type', 'woo-flashpay'),
            self::REFUND => _x('Refund', 'Operation type', 'woo-flashpay'),
            self::REFUND_REVERSE => _x('Reverse refund', 'Operation type', 'woo-flashpay'),
            self::MANUAL_CHANGE => _x('Manual change', 'Operation type', 'woo-flashpay'),
            self::ACCOUNT_VERIFICATION => _x('Account verification', 'Operation type', 'woo-flashpay'),
            self::CREATE_CASH_VOUCHER => _x('Create cash voucher', 'Operation type', 'woo-flashpay'),
            self::COMMISSION => _x('Commission', 'Operation type', 'woo-flashpay'),
            self::INCREMENTAL => _x('Incremental', 'Operation type', 'woo-flashpay'),
            self::INVOICE => _x('Invoice', 'Operation type', 'woo-flashpay'),
            self::CUSTOMER_ACTION => _x('Customer action', 'Operation type', 'woo-flashpay'),
            self::PAYMENT_CONFIRMATION => _x('Payment confirmation', 'Operation type', 'woo-flashpay'),
            self::CAPTURE_SETTLEMENT => _x('Capture settlement', 'Operation type', 'woo-flashpay'),
        ];
    }

    private static function compile_codes()
    {
        $data = [];

        foreach (self::get_status_names() as $key => $value) {
            $data[$key] = str_replace(' ', '-', $key);
        }

        return $data;
    }
}
