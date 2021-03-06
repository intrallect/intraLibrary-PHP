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
    private static $IS_REGISTERED = array();

    /**
     * Register on the SPL autoloader stack
     *
     * @param string $path [optional] autoload path
     * @return boolean true on success
     */
    public static function register($path = null)
    {
        if (empty(self::$IS_REGISTERED[$path])) {
            self::$IS_REGISTERED[$path] = true;

            // If composer is available
            // check to see if it has already loaded this namespace
            if (class_exists('ComposerAutoloaderInit')) {
                $prefixes = \ComposerAutoloaderInit::getLoader()->getPrefixes();
                if (isset($prefixes[__NAMESPACE__])) {
                    return true;
                }
            }

            return spl_autoload_register(array(new self($path), 'load'));
        }

        return true;
    }

    private $path;

    /**
     * Create a loader
     *
     * @param string $path [opional] the classpath to search
     */
    public function __construct($path = null)
    {
        if ($path) {
            if (is_dir($path)) {
                $this->path = realpath($path);
            } else {
                throw new IntraLibraryException("Unable to create loader: $path is not a directory");
            }
        } else {
            $this->path = dirname(__DIR__);
        }
    }

    /**
     * Classloader
     *
     * @param string $class the classname to load
     * @return boolean false if class was not loaded
     */
    public function load($class)
    {
        if ($file = $this->findFile($class)) {
            include $file;
            return true;
        }
    }

    /**
     * Finds the path to the file where the class is defined.
     *
     * @param string $class The name of the class
     *
     * @return string|null The path, if found
     */
    private function findFile($class)
    {
        if ('\\' == $class[0]) {
            $class = substr($class, 1);
        }

        if (($pos = strrpos($class, '\\')) !== false) {
            $classPath = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, 0, $pos)) . DIRECTORY_SEPARATOR;
            $className = substr($class, $pos + 1);
        } else {
            $classPath = '';
            $className = $class;
        }

        $filePath = $this->path . DIRECTORY_SEPARATOR . $classPath . $className . '.php';
        if (file_exists($filePath)) {
            return $filePath;
        }

        return false;
    }
}

