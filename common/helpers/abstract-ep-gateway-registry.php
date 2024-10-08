<?php

defined('ABSPATH') || exit;

/**
 * <h2>Registry of internal library modules.</h2>
 *
 * @class    Ep_Gateway_Registry
 * @version  2.0.0
 * @package  Ep_Gateway/Helpers
 * @category Class
 * @abstract
 * @internal
 */
abstract class Ep_Gateway_Registry
{
    /**
     * <h2>An array of instances of various modules.</h2>
     *
     * @var array
     * @since 2.0.0
     */
    private static $instances;

    /**
     * <h2>Closed constructor of base registry.</h2>
     * <p>To instantiate modules, you need to use the following of the static methods:<br/>
     *      - {@see Ep_Gateway_Registry::get_by_class()} Get instance by class name.<br/>
     *      - {@see Ep_Gateway_Registry::get_instance()} Get an instance from the called class.<br/>
     * </p>
     * @since 2.0.0
     */
    private function __construct()
    {
        $this->init();
    }

    /**
     * <h2>Sets up a new object.</h2>
     * <p>Can be used for adding hooks and filters.</p>
     * <p>Executed when an object instance is created.</p>
     *
     * @since 2.0.0
     * @return void
     */
    protected function init()
    {
        // Default: do nothing
    }

    /**
     * <h2>Returns an object instance by class name.</h2>
     *
     * @param string $className <p>Object class name.</p>
     * @since 2.0.0
     * @return static An instance of an object created by the class name.
     */
    final public static function get_by_class($className)
    {
        if (!isset (self::$instances[$className])) {
            self::$instances[$className] = new $className();
        }

        return self::$instances[$className];
    }

    /**
     * <h2>Return an instance of an object by the class being called.</h2>
     *
     * @since 2.0.0
     * @return static An instance of the current object.
     */
    final public static function get_instance()
    {
        $className = get_called_class();

        return self::get_by_class($className);
    }

    /**
     * <h2>Cloning disabled.</h2>
     * @since 2.0.0
     */
    private function __clone()
    {
    }
}
