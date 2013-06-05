<?php

namespace IntraLibrary;

/**
 * Configurable Caching tool for IntraLibrary
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 *
 */
class Cache extends Proxy
{
    /**
     * Load from the cache
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param string $key the key to load
     * @return mixed
     */
    public static function load($key)
    {
        return parent::invoke(__FUNCTION__, func_get_args());
    }

    /**
     * Save to the cache
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param string  $key     the key
     * @param mixed   $data    the data
     * @param integer $expires expiry length
     * @return mixed
     */
    public static function save($key, $data, $expires = null)
    {
        return parent::invoke(__FUNCTION__, func_get_args());
    }
}

