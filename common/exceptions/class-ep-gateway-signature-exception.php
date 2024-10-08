<?php

defined('ABSPATH') || exit;

/**
 * Ep_Gateway_Signature_Exception class
 *
 * @class   Ep_Gateway_Signature_Exception
 * @since   2.0.0
 * @package Ep_Gateway/Exceptions
 * @category Class
 */
class Ep_Gateway_Signature_Exception extends Ep_Gateway_Exception
{
    /**
     * <p>The value or key of an invalid parameter</p>
     * @var string
     * @since 2.0.0
     */
    private $parameter;

    /**
     * <h2>Signature exception constructor.</h2>
     *
     * @param string $parameter <p>The value or key of an invalid parameter.</p>
     * @param string $message <p>Exception message.</p>
     * @param int $code [optional] <p>Error code. Default: {@see Ep_Gateway_Error::UNKNOWN_ERROR}
     * @param ?Exception $previous [optional] <p>Previous exception.</p>
     */
    public function __construct(
        $message,
        $parameter,
        $code = Ep_Gateway_Error::UNKNOWN_ERROR,
        Exception $previous = null
    ) {
        $this->parameter = $parameter;
        parent::__construct($message, $code, $previous);
    }

    /**
     * <h2>Returns the value or key of an invalid parameter.</h2>
     * @return string
     */
    final public function getParameter()
    {
        return $this->parameter;
    }

    /**
     * @inheritDoc
     * @return array[]
     */
    protected function prepare_message()
    {
        return [
            [
                $this->get_base_message(),
                WC_Log_Levels::ERROR
            ],
            [
                sprintf(_x('Invalid parameter: %s', 'Exception message', 'woo-flashpay'), $this->getParameter()),
                WC_Log_Levels::ERROR,
            ],
            [
                sprintf(
                    _x('Prohibited symbol: %s', 'Exception message', 'woo-flashpay'),
                    Ep_Gateway_Signer::VALUE_SEPARATOR
                ),
                WC_Log_Levels::ERROR,
            ],
        ];
    }
}