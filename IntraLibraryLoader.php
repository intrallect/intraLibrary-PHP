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
	private static $CLASSES = array();
	
	/**
	 * Register the loader SPL autoloader stack
	 *
	 * @return void
	 */
	public static function registerAutoloader()
	{
		self::$CLASSES = self::getClasses();
		spl_autoload_register(__CLASS__ . '::load');
	}
	
	/**
	 * Classloader
	 * 
	 * @param string $classname the classname to load
	 * @return boolean FALSE if class was not loaded
	 */
	public static function load($classname)
	{
		if (empty(self::$CLASSES[$classname]) ||
			!include(self::$CLASSES[$classname]))
		{
			return FALSE;
		}
	}
	
	/**
	 * Get all PHP classes part of IntraLibrary
	 *
	 * @return array an associative array mapping classname => filename
	 */
	public static function getClasses()
	{
		$classes 	= array();
		
		foreach (self::_getFiles(dirname(__FILE__)) as $file)
		{
			$pathinfo = pathinfo($file);
			if (isset($pathinfo['extension']) && $pathinfo['extension'] == 'php')
			{
				$classes[$pathinfo['filename']] = $file;
			}
		}
		
		return $classes;
	}
	
	/**
	 * Get all files contained in this directory (looks recursively into subdirectories)
	 * 
	 * @param string $directory the directory to look into
	 * @return array a list of files found
	 */
	private static function _getFiles($directory)
	{
		$directory 	= self::_sanitiseFilepath($directory);
		$list 		= array();
		
		if ($handle = opendir($directory))
		{
			while (($file = readdir($handle)) !== FALSE)
			{
				if (is_dir($directory . $file) && $file != '.' && $file != '..')
				{
					$sublist = self::_getFiles($directory . $file);
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
	
	/**
	 * Santise a filepath
	 * 
	 * @param string  $path         the path to sanitise
	 * @param boolean $append_slash if false won't append a slash
	 * @return string
	 */
	private static function _sanitiseFilepath($path, $append_slash = TRUE)
	{
		// Convert to correct UNIX paths
		$path = str_replace('\\', '/', $path);
		$path = str_replace('../', '/', $path);
		// replace // with / except when preceeded by :
		$path = preg_replace("/([^:])\/\//", "$1/", $path);
	
		// Sort trailing slash
		$path = trim($path);
		// rtrim defaults plus /
		$path = rtrim($path, " \n\t\0\x0B/");
	
		if ($append_slash)
		{
			$path = $path . '/';
		}
	
		return $path;
	}
}
