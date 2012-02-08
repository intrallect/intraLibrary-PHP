<?php
/**
 * IntraLibraryProxy is used to register client implementations of functions
 * to be used with IntraLibrary.
 * 
 * Subclasses should define a public static function for each available action as follows:
 * 
 * public static function actionX($argA, $argB) {
 * 	parent::invoke(__FUNCTION__, $argA, $argB);
 * }
 * 
 * @requires PHP 5.3
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 *
 */
abstract class IntraLibraryProxy
{
	private static $_actions = array();
	private static $_callbacks = array();
	
	/**
	* Register a proxy function
	* Inspect the extending class to see which functions should be registered
	*
	* @param string   $action   the action to register
	* @param callable $callback the callback to register with this action
	* @throws IntraLibraryException
	* @return void
	*/
	public static function register($action, $callback)
	{
		if (!in_array($action, self::_getActions()))
		{
			throw new IntraLibraryException($action . ' is not a valid action');
		}
		
		if (!is_callable($callback))
		{
			throw new IntraLibraryException($callback . ' does not exist or is not callable');
		}
		
		if (isset(self::$_callbacks[$action]))
		{
			throw new IntraLibraryException($action . ' has already been registered');
		}
		
		self::$_callbacks[$action] = $callback;
	}
	
	/**
	 * Invoke a registered action
	 * 
	 * @throws IntraLibraryException
	 * @return mixed
	 */
	protected static function invoke()
	{
		$args = func_get_args();
		
		if (count($args) < 1)
		{
			throw new IntraLibraryException('Not enough arguments');
		}
		
		$name = $args[0];
		
		if (empty(self::$_callbacks[$name]))
		{
			throw new IntraLibraryException("No proxy action defined for '$name'");
		}
		
		return call_user_func_array(self::$_callbacks[$name], array_slice($args, 1));
	}
	
	
	/**
	 * Retrieve all actions defined by the extending class
	 * 
	 * @return array
	 */
	private static function _getActions()
	{
		// get the called class
		$calledClass		= get_called_class();
		if (!$calledClass)
		{
			return array();
		}
		
		if (isset(self::$_actions[$calledClass]))
		{
			return self::$_actions[$calledClass];
		}
		
		self::$_actions[$calledClass] = array();
		
		// reflect in and find all public static functions defined by that class
		$reflectionClass 	= new ReflectionClass($calledClass);
		foreach ($reflectionClass->getMethods() as $method)
		{
			if ($method->isStatic() && $method->isPublic() && $method->class == $calledClass)
			{
				self::$_actions[$calledClass][] = $method->name;
			}
		}
		
		return self::$_actions[$calledClass];
	}
}
