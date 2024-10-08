<?php

defined('ABSPATH') || exit;

/**
 * Ep_Gateway_Info_ACS
 *
 * Contains the result of the customer authentication by using 3‑D Secure.
 * This object is available in callback, if the payment was made with the card that supports 3‑D Secure.
 *
 * @class    Ep_Gateway_Info_ACS
 * @version  2.0.0
 * @package  Ep_Gateway/Info
 * @category Class
 */
class Ep_Gateway_Info_ACS extends Ep_Gateway_Json
{
    // region Constants

    /**
     * Label for URL of the issuing bank ACS page.
     */
    const FIELD_ACS_URL = 'acs_url';

    /**
     * Label for merchant technical data in the payment system.
     */
    const FIELD_MD = 'md';

    /**
     * Label for the authentication request that need to be sent to the issuing bank.
     */
    const FIELD_PA_REQ = 'pa_req';

    // endregion

    /**
     * <h2>Returns URL of the issuing bank ACS page.</h2>
     * @return string
     */
    public function get_acs_url()
    {
        $this->try_get_string($acs_url, self::FIELD_ACS_URL);
        return $acs_url;
    }

    /**
     * <h2>Returns the merchant technical data in the payment system.</h2>
     *
     * @return string
     */
    public function get_md()
    {
        $this->try_get_string($md, self::FIELD_MD);
        return $md;
    }

    /**
     * <h2>Returns the authentication request that need to be sent to the issuing bank.</h2>
     * <p>The parameter contains encoded information about the cardholder, the merchant, and the payment.</p>
     *
     * @return string
     */
    public function get_pa_req()
    {
        $this->try_get_string($pa_req, self::FIELD_PA_REQ);
        return $pa_req;
    }
}