<?php

namespace IntraLibrary;

/**
 * Debugging proxy for IntraLibrary
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 *
 */
class Debug extends Proxy
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
