<?php

namespace IntraLibrary;

/**
 * Configuration store for IntraLibrary
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 *
 */
class Configuration
{
    private static $CONFIG;

    /**
     * Set the value for a configuration setting or override all settings
     *
     * @param string $name  The setting name
     * @param mixed  $value (optional) The setting value
     * @return void
     */
    public static function set($name, $value = null)
    {
        self::init();

        if ($value == null && (is_array($name) || is_object($name))) {
            self::$CONFIG = (object) $name;
        } elseif (is_string($name)) {
            self::$CONFIG->{$name} = $value;
        }
    }

    /**
     * Get a the value for a configuration setting
     *
     * @param string $name The setting name
     * @return mixed
     */
    public static function get($name = null)
    {
        self::init();

        if ($name === null) {
            return self::$CONFIG;
        }

        return empty(self::$CONFIG->$name) ? null : self::$CONFIG->$name;
    }

    /**
     * Initalise the config object
     *
     * @return void
     */
    private static function init()
    {
        if (empty(self::$CONFIG)) {
            self::$CONFIG = new \stdClass();
        }
    }
}

