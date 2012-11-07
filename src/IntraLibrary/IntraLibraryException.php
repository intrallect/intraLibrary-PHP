<?php

namespace IntraLibrary;

use \Exception;

/**
 * IntraLibraryException class
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
class IntraLibraryException extends Exception
{
	const USER_EXISTS = 1;

	/**
	 * Create an IntraLibraryException object
	 *
	 * @param string  $message the message
	 * @param integer $code    the exception code (pass -1 to autodetect the code based on message)
	 */
	public function __construct($message, $code = 0)
	{
		if ($code === -1)
		{
			if (preg_match('/A User with username \"(.)*\" already exists, please use a different username./', $message))
			{
				$code = self::USER_EXISTS;
			}
		}

		parent::__construct($message, $code);
	}
}
