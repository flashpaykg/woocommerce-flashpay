<?php

defined('ABSPATH') || exit;

/**
 * Ep_Gateway_Info_Recurring
 *
 * @class    Ep_Gateway_Info_Recurring
 * @version  2.0.0
 * @package  Ep_Gateway/Info
 * @category Class
 */
class Ep_Gateway_Info_Recurring extends Ep_Gateway_Json
{
    // region Constants

    /**
     * Label for COF payment ID.
     */
    const FIELD_ID = 'id';

    /**
     * Label for COF purchase currency.
     */
    const FIELD_CURRENCY = 'currency';

    /**
     * Label for COF FLASHPAY payment ID.
     */
    const FIELD_REGISTER_PAYMENT_ID = 'register_payment_id';

    /**
     * Label for COF purchase status.
     */
    const FIELD_STATUS = 'status';

    /**
     * Label for COF purchase type.
     */
    const FIELD_TYPE = 'type';

    /**
     * Label for expiration date of the COF purchase ID.
     */
    const FIELD_VALID_THRU = 'valid_thru';

    // endregion

    /**
     * <h2>Returns COF payment identifier.</h2>
     *
     * @return ?int
     */
    public function get_id()
    {
        if ($this->try_get_int($id, self::FIELD_ID)) {
            return $id;
        }

        return null;
    }

    /**
     * <h2>Returns COF purchase currency.</h2>
     *
     * @return ?string Currency in ISO 4217 alpha-3 format, if available, or <b>NULL</b> otherwise.
     */
    public function get_currency()
    {
        if ($this->try_get_string($currency, self::FIELD_CURRENCY)) {
            return $currency;
        }

        return null;
    }

    /**
     * <h2>Returns COF FLASHPAY payment ID.</h2>
     *
     * @return ?string
     */
    public function get_register_payment_id()
    {
        if ($this->try_get_string($id, self::FIELD_REGISTER_PAYMENT_ID)) {
            return $id;
        }

        return null;
    }

    /**
     * <h2>Returns COF purchase status.</h2>
     *
     * @return ?string <p>
     * Possible values:<br/>
     *      - {@see Ep_Gateway_Recurring_Status::ACTIVE} COF is active.<br/>
     *      - {@see Ep_Gateway_Recurring_Status::CANCELLED} COF is cancelled.<br/>
     *      - <b>NULL</b> if not defined.<br/>
     * </p>
     */
    public function get_status()
    {
        if ($this->try_get_string($status, self::FIELD_STATUS)) {
            if (array_key_exists($status, Ep_Gateway_Recurring_Status::get_status_names())) {
                return $status;
            }
        }

        return null;
    }

    /**
     * <h2>Returns COF purchase type.</h2>
     *
     * @return ?string
     */
    public function get_type()
    {
        if ($this->try_get_string($type, self::FIELD_TYPE)) {
            if (array_key_exists($type, Ep_Gateway_Recurring_Types::get_status_names())) {
                return $type;
            }
        }

        return null;
    }

    /**
     * <h2>Returns expiration date of the COF purchase ID.</h2>
     *
     * @return ?DateTime
     */
    public function get_valid_thru()
    {
        if ($this->try_get_object($date, self::FIELD_VALID_THRU)) {
            return $date;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    protected function unpackRules()
    {
        return [
            self::FIELD_VALID_THRU => function ($value) {
                return DateTime::createFromFormat(DateTime::RFC3339, $value);
            }
        ];
    }
}