<?php 
/**
 * Configuration store for IntraLibrary
 * 
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 *
 */
class IntraLibraryConfiguration
{
	private static $CONFIG;
	
	/**
	 * Set the value for a configuration setting
	 * 
	 * @param string $name  The setting name
	 * @param mixed  $value The setting value
	 * @return void
	 */
	public static function set($name, $value)
	{
		self::_init();
		
		self::$CONFIG->{$name} = $value;
	}
	
	/**
	 * Get a the value for a configuration setting
	 * 
	 * @param string $name The setting name
	 * @return mixed 
	 */
	public static function get($name = NULL)
	{
		self::_init();
		
		if ($name === NULL)
		{
			return self::$CONFIG;
		}
		
		return empty(self::$CONFIG->$name) ? NULL : self::$CONFIG->$name;
	}
	
	/**
	 * Initalise the config object
	 *
	 * @return void
	 */
	private static function _init()
	{
		if (empty(self::$CONFIG))
		{
			self::$CONFIG = new stdClass();
		}
	}
}
