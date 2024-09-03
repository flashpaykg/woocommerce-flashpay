<?php

defined('ABSPATH') || exit;

/**
 * Ep_Gateway_Error class
 *
 * @class   Ep_Gateway_Error
 * @since   2.0.0
 * @package Ep_Gateway/Exceptions
 */
class Ep_Gateway_Error_Exception extends Ep_Gateway_Exception
{
    private $severity;

    public function __construct(
        $message = '',
        $code = 0,
        $severity = E_ERROR,
        $filename = null,
        $line = null,
        Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->severity = $severity;
        $this->file = $filename;
        $this->line = $line;
    }

    final public function getSeverity()
    {
        return $this->severity;
    }

    protected function prepare_message()
    {
    }
}