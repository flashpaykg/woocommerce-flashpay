<?php

defined('ABSPATH') || exit;

/**
 * Ep_Gateway_Duplicate_Exception class
 *
 * @class   Ep_Gateway_Duplicate_Exception
 * @since   2.0.0
 * @package Ep_Gateway/Exceptions
 * @internal
 */
class Ep_Gateway_Duplicate_Exception extends Ep_Gateway_Exception
{
    /**
     * @var string
     * @since 2.0.0
     */
    private $key;

    /**
     * Exception constructor.
     *
     * @param mixed $key The duplicate key name.
     * @param int $errorCode [optional] Error code. Default: {@see Ep_Gateway_Error::DUPLICATE}.
     * @param string $message [optional] Exception message. Default: none.
     * @param ?Exception $previous [optional] Previous exception. Default: none.
     * @since 2.0.0
     */
    public function __construct(
        $key,
        $errorCode = Ep_Gateway_Error::DUPLICATE,
        $message = null,
        Exception $previous = null
    ) {
        $this->key = $key;

        if ($message === null) {
            $message = _x('Key is already exists in the current array', 'Exception message', 'woo-flashpay');
        }

        parent::__construct($message, $errorCode, $previous);
    }

    /**
     * Returns duplicate key name.
     *
     * @since 2.0.0
     * @return mixed
     */
    final public function getKey()
    {
        return $this->key;
    }

    /**
     * @inheritDoc
     * @since 2.0.0
     * @return string[][]
     */
    protected function prepare_message()
    {
        return [
            [
                $this->get_base_message(),
                WC_Log_Levels::ALERT
            ],
            [
                sprintf(
                    /* translators: %s: Duplicate key name */
                    _x('Duplicated key: %s', 'Exception message', 'woo-flashpay'),
                    $this->getKey()
                ),
                WC_Log_Levels::ERROR
            ]
        ];
    }
}
