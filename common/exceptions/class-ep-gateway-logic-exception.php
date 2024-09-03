<?php

defined('ABSPATH') || exit;

/**
 * Ep_Gateway_Logic_Exception class
 *
 * @class   Ep_Gateway_Logic_Exception
 * @since   2.0.0
 * @package Ep_Gateway/Exceptions
 * @category Class
 */
class Ep_Gateway_Logic_Exception extends Ep_Gateway_Exception
{

    protected function prepare_message()
    {
        return [
            [
                $this->get_base_message(),
                WC_Log_Levels::ERROR
            ]
        ];
    }
}