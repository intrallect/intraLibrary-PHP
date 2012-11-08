<?php

namespace IntraLibrary;

/**
 * Class loader for IntraLibrary-PHP
 *
 * Note: This is unnecessary if IntraLibrary-PHP is loaded via composer
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 *
 */
class Loader
{
	private static $_IS_REGISTERED = FALSE;

	/**
	 * Register on the SPL autoloader stack
	 *
	 * @return void
	 */
	public static function register()
	{
		if (self::$_IS_REGISTERED) return TRUE;

		self::$_IS_REGISTERED = TRUE;

		// If composer is available
		// check to see if it has already loaded this namespace
		if (class_exists('ComposerAutoloaderInit'))
		{
			$prefixes = \ComposerAutoloaderInit::getLoader()->getPrefixes();
			if (isset($prefixes[__NAMESPACE__]))
			{
				return TRUE;
			}
		}

		return spl_autoload_register(array(new self(), 'load'));
	}

	/**
	 * Loads the SWORDAPPClient class.
	 * TODO: find a cleaner approach to packaging & loading swordapp-php-library
	 *
	 * @return void
	 */
	public static function loadSWORDAPP_PHP_CLIENT()
	{
		if (!class_exists('SWORDAPPClient', FALSE))
		{
			include __DIR__ . '/../swordapp-php-library/swordappclient.php';
		}
	}

	/**
	 * Classloader
	 *
	 * @param string $class the classname to load
	 * @return boolean FALSE if class was not loaded
	 */
	public function load($class)
	{
		if ($file = $this->_findFile($class))
		{
            include $file;

            return TRUE;
        }
	}

	/**
	 * Finds the path to the file where the class is defined.
	 *
	 * @param string $class The name of the class
	 *
	 * @return string|NULL The path, if found
	 */
	private function _findFile($class)
	{
		if ('\\' == $class[0])
		{
			$class = substr($class, 1);
		}

		if (($pos = strrpos($class, '\\')) !== FALSE)
		{
			$classPath = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, 0, $pos)) . DIRECTORY_SEPARATOR;
			$className = substr($class, $pos + 1);
		}
		else
		{
			$classPath = '';
			$className = $class;
		}

		$filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . $classPath . $className . '.php';
		if (file_exists($filePath))
		{
			return $filePath;
		}

		return FALSE;
	}
}
