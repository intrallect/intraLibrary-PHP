<?php

/**
 * Class loader for IntraLibrary-PHP
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 *
 */
class IntraLibraryLoader
{
	private static $_instance;

	/**
	 * Register the loader SPL autoloader stack
	 *
	 * @return void
	 */
	public static function registerAutoloader()
	{
		spl_autoload_register(array(self::getInstance(), 'load'));
	}

	/**
	 * Get the main instance of the autoloader
	 *
	 * @return IntraLibraryLoader
	 */
	public static function getInstance()
	{
		return self::$_instance ? self::$_instance : (self::$_instance = new self(__DIR__));
	}

	private $classes;
	private $baseDir;

	/**
	 * Create an IntraLibraryLoader
	 *
	 * @param strign $baseDir the directory that hosts PHP classes
	 */
	public function __construct($baseDir)
	{
		$this->baseDir = $baseDir;
		$this->loadClasses();
	}

	/**
	 * Classloader
	 *
	 * @param string $classname the classname to load
	 * @return boolean FALSE if class was not loaded
	 */
	public function load($classname)
	{
		if (empty($this->classes[$classname]) ||
			!include($this->classes[$classname]))
		{
			return FALSE;
		}
	}

	/**
	 * Get all PHP classes part of IntraLibrary
	 *
	 * @return void
	 */
	public function loadClasses()
	{
		$this->classes = array();

		foreach (self::_getFiles($this->baseDir) as $file)
		{
			$pathinfo = pathinfo($file);
			if (isset($pathinfo['extension']) && $pathinfo['extension'] == 'php')
			{
				$this->classes[$pathinfo['filename']] = $file;
			}
		}
	}

	/**
	 * Get all classes found by this loader
	 *
	 * @return array an associative array mapping classname => filename
	 */
	public function getClasses()
	{
		if (empty($this->classes))
		{
			$this->loadClasses();
		}

		return $this->classes;
	}

	/**
	 * Get all files contained in this directory, and it's subdirectories
	 *
	 * @param string $directory the directory to look into
	 * @return array a list of files found
	 */
	private function _getFiles($directory)
	{
		$directory 	= rtrim($directory, '/') . '/';
		$list 		= array();

		if ($handle = opendir($directory))
		{
			while (($file = readdir($handle)) !== FALSE)
			{
				if (is_dir($directory . $file) && $file != '.' && $file != '..')
				{
					$sublist = $this->_getFiles($directory . $file);
					$list = array_merge($list, $sublist);
				}
				else if (is_file($directory . $file))
				{
					$list[] = $directory . $file;
				}

			}
			closedir($handle);
		}

		return $list;
	}
}
