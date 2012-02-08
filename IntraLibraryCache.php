<?php
/**
 * Configurable Caching tool for IntraLibrary
 * 
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 *
 */
class IntraLibraryCache extends IntraLibraryProxy
{
	/**
	 * Load from the cache
	 * 
	 * @param string $key the key to load
	 * @return mixed
	 */
	public static function load($key)
	{
		return parent::invoke(__FUNCTION__, $key);
	}
	
	/**
	 * Save to the cache
	 * 
	 * @param string  $key     the key
	 * @param mixed   $data    the data
	 * @param integer $expires expiry length
	 * @return mixed
	 */
	public static function save($key, $data, $expires = NULL)
	{
		return parent::invoke(__FUNCTION__, $key, $data, $expires);
	}
}
