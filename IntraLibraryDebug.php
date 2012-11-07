<?php

/**
 * Debugging proxy for IntraLibrary
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 *
 */
class IntraLibraryDebug extends IntraLibraryProxy
{
	/**
	 * Send a message to the screen
	 *
	 * @param string $message the message
	 * @return mixed
	 */
	public static function screen($message)
	{
		return parent::invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * Send a message to the log
	 *
	 * @param string $message the message
	 * @return mixed
	 */
	public static function log($message)
	{
		return parent::invoke(__FUNCTION__, func_get_args());
	}
}
