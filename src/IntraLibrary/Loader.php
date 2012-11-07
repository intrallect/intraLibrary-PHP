<?php

namespace IntraLibrary;

/**
 * Class loader for IntraLibrary-PHP
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 *
 */
class Loader
{
	/**
	 * Register on the SPL autoloader stack
	 *
	 * @return void
	 */
	public static function register()
	{
		spl_autoload_register(array(new self(), 'load'));
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
